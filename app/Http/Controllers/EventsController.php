<?php

namespace App\Http\Controllers;

use App\Http\Requests\EventsRequest;
use App\Services\Slack;

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
        if($request->event['reaction'] === config('slack.jobpostings_helper.reaction') && $this->channelWithin($request->event['item']['channel'], config('slack.jobpostings_helper.channels'))){
            $result = $this->slack->sendOrUpdateThreadedMessage($request->event['item']['channel'], $request->event['item']['ts'], config('slack.jobpostings_helper.blocks'), $request);
        }

        return response()->json(['status' => 'success'], 200);
    }



    protected function channelWithin($needle, $haystack)
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
