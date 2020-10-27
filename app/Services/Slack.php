<?php

namespace App\Services;

use App\Http\Requests\EventsRequest;
use GuzzleHttp\Client as Guzzle;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class Slack {

    protected $apiEndpoint;
    protected $bot_oauth_access;
    protected $bot_id;

    public $guzzle;

    protected $cache_default_ttl;

    public function __construct(Guzzle $guzzle = null)
    {
        $this->apiEndpoint = config('slack.api_endpoint');
        $this->bot_oauth_access = config('slack.bot_oauth_access');
        $this->bot_id = config('slack.bot_id');
        $this->cache_default_ttl = config('slack.cache.default_ttl');
        $this->guzzle = $guzzle ?: new Guzzle;
    }

    public function getSpecificMessage($conversation, $timestamp)
    {
        $endpoint = 'conversations.history';
        $payload = [
            'token' => $this->bot_oauth_access,
            'channel' => $conversation,
            'latest' => $timestamp,
            'inclusive' => 1,
            'limit' => 1
        ];
        return json_decode($this->guzzle->get($this->apiEndpoint . $endpoint, ['query' => $payload])->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
    }

    public function sendOrUpdateThreadedMessage($conversation, $timestamp, $message, EventsRequest $request)
    {
        $parentMessage = $this->getSpecificMessage($conversation, $timestamp);
        $threadTimestamp = $parentMessage['messages'][0]['thread_ts'] ?? $parentMessage['messages'][0]['ts'];

        $ourMessageTimestamp = Cache::tags(['Conversation:'.$conversation, 'parent_thread_message_timestamp'])->get($threadTimestamp);

        if($ourMessageTimestamp){
            Log::debug('Initial Message already sent', ['conversation' => $conversation, 'timestamp' => $ourMessageTimestamp]);
            $users = Cache::tags(['Conversation:'.$conversation, 'users'])->get($ourMessageTimestamp);
            if(is_array($users) && !in_array($request->event['user'], $users)){
                $users[] = $request->event['user'];
                Cache::tags(['Conversation:'.$conversation, 'users'])->put($ourMessageTimestamp, $users, $this->cache_default_ttl);
                $this->updateExistingMessage($conversation, $message, $ourMessageTimestamp, $users);
            }
            return true;
        }

        $response = $this->sendMessageAsThreadResponse($conversation, $message, $threadTimestamp, $request->event['user']);

        Cache::tags(['Conversation:'.$conversation, 'parent_thread_message_timestamp'])->put($threadTimestamp, $response['ts'], $this->cache_default_ttl);
        Cache::tags(['Conversation:'.$conversation, 'users'])->put($response['ts'], [$request->event['user']], $this->cache_default_ttl);

        return $response;

    }

    public function sendMessageAsThreadResponse($conversation, $message, $timestamp, $user)
    {
        Log::debug('Sending Initial Thread Response', ['conversation' => $conversation, 'timestamp' => $timestamp, 'users' => [$user]]);
        $endpoint = 'chat.postMessage';
        $message[] = $this->appendUsersBlock([$user]);
        $payload = [
            'token' => $this->bot_oauth_access,
            'channel' => $conversation,
            'thread_ts' => $timestamp,
            'text' => $message[0]['text']['text'],
            'blocks' => json_encode($message, JSON_THROW_ON_ERROR),
            'link_names' => true,
        ];
        return json_decode($this->guzzle->post($this->apiEndpoint.$endpoint, ['form_params' => $payload])->getBody()->getContents(),true);
    }

    public function updateExistingMessage($conversation, $message, $timestamp, $users)
    {
        Log::debug('Updating Existing Message', ['conversation' => $conversation, 'timestamp' => $timestamp, 'users' => $users]);
        $endpoint = 'chat.update';
        $message[] = $this->appendUsersBlock($users);
        $payload = [
            'token' => $this->bot_oauth_access,
            'channel' => $conversation,
            'ts' => $timestamp,
            'blocks' => json_encode($message, JSON_THROW_ON_ERROR),
        ];
        return json_decode($this->guzzle->post($this->apiEndpoint.$endpoint, ['form_params' => $payload])->getBody()->getContents(),true);
    }

    protected function appendUsersBlock(array $users)
    {
        $userString = '<@' . implode('> & <@', $users) . '>';
        return  [
            'type' => 'section',
            'text' =>
                [
                    'type' => "mrkdwn",
                    'text' => $userString . ' would appreciate the change.',
                ]
        ];
    }


}
