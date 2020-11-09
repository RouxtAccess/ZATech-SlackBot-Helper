<?php

namespace App\Http\Controllers;

use App\Http\Requests\EventsRequest;
use App\Services\Slack;
use Illuminate\Support\Facades\Log;

class EventsController extends Controller
{
    private Slack $slack;

    public function __construct(Slack $slack)
    {
        $this->slack = $slack;
    }

    public function action(EventsRequest $request)
    {
        // ToDo Challenge Verification
        if(method_exists($this, 'events_' . $request->type))
        {
            return $this->{'events_' . $request->type}($request);
        }
        if(isset($request->event)
            && is_array($request->event)
            && method_exists($this, 'events_' . $request->event['type']))
        {
            return $this->{'events_' . $request->event['type']}($request);
        }
        return response()->json(['status' => 'error', 'error' => 'unable to find matching action type [events_' . $request->type . ']'], 422);
    }

    protected function events_url_verification(EventsRequest $request)
    {
        return response()->json(['challenge' => $request->challenge]);
    }

    protected function events_reaction_added(EventsRequest $request)
    {
        // Jobpostings
        if($request->event['reaction'] === config('slack.jobpostings_helper.reaction') && $this->withinArray($request->event['item']['channel'], config('slack.jobpostings_helper.channels')))
        {
            $result = $this->slack->sendOrUpdateThreadedMessage($request->event['item']['channel'], $request->event['item']['ts'], config('slack.jobpostings_helper.blocks'), $request);
        }

        return response()->json(['status' => 'success'], 200);
    }

    protected function events_event_callback(EventsRequest $request)
    {
        // Invites
        if((isset($request->event['subtype']) === false) && $this->withinArray($request->event['user'],config('slack.invite_helper.users')) && $this->withinArray($request->event['channel'], config('slack.invite_helper.channels')))
        {
            $result = $this->handleInviteMessage($request);
        }

        return response()->json(['status' => 'success'], 200);
    }

    protected function handleInviteMessage(EventsRequest $request)
    {
        // Ensure that it is a user invite and not a shared channel request
        if(strpos($request->event['text'], 'requested to invite one person to this workspace') === false)
        {
            Log::error('Not a user invite message', ['text' => $request->event['text']]);
            return false;
        }

        // Get the Inviter's user string
        $matches = [];
        if(!preg_match("/<@([^>]+)>/", $request->event['text'], $matches)){
            Log::error('Unable to match user string', ['text' => $request->event['text']]);
            return false;
        }
        $inviter = $matches[1];

        // Get the email address
        if(!preg_match("/<mailto:([^|]+)/", $request->event['attachments'][0]['text'], $matches))
        {
            Log::error('Unable to match email', ['text' => $request->event['text']]);
            return false;
        }
        $inviteeEmail = $matches[1];
        Log::info('Found message info', ['user' => $inviter, 'email' => $inviteeEmail]);

        // Send the user a message
        $this->slack->sendMessage($inviter, config('slack.invite_helper.user_response.blocks')[0]['text']['text'], config('slack.invite_helper.user_response.blocks'), 'robot_face');
        // Send the invitee an email?

        // Thread response the original message
        $this->slack->sendMessage($request->event['channel'], config('slack.invite_helper.thread_response.blocks')[0]['text']['text'], config('slack.invite_helper.thread_response.blocks'), 'robot_face', $request->event['ts']);
        foreach (config('slack.invite_helper.thread_response.emojis') as $emoji)
        {
            $this->slack->addReaction($request->event['channel'], $request->event['ts'], $emoji);
        }

        return true;
    }

    protected function withinArray($needle, $haystack)
    {
        if (count($haystack) === 0) {
            return true;
        }

        if(in_array($needle, $haystack, true)) {
            return true;
        }

        return false;
    }

}
