<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Contentful\Delivery\Client as DeliveryClient;
use Analytics;
use Spatie\Analytics\Period;
use Charts;


class AdminController extends Controller
{

    /**
     * @var DeliveryClient
     */
    private $client;

    public function __construct(DeliveryClient $client) {
        $this->client = $client;
    }

    public function index() {
        return view('admin.index');
    }

    public function pages() {
        $popular = Analytics::fetchMostVisitedPages(Period::days(30), 10);
        $referrers = Analytics::fetchTopReferrers(Period::days(30), 10);
        return view('admin.pages')->with('popular', $popular)->with('referrers', $referrers);
    }

    public function analytics(Request $request) {
        if($request->period == "week") {
            $period = 7;
        }
        elseif($request->period == "month") {
            $period = 30;
        }
        elseif($request->period == "year") {
            $period = 365;
        }
        else {
            $period = 30;
        }
        $sessions = Analytics::performQuery(Period::days($period), 'ga:sessions')->totalsForAllResults["ga:sessions"]; //Total number of sessions
        $bounceRate = Analytics::performQuery(Period::days($period), 'ga:bounceRate')->totalsForAllResults["ga:bounceRate"]; //Number of sessions ended from the entrance page
        $totalSessionTime = Analytics::performQuery(Period::days($period), 'ga:sessionDuration')->totalsForAllResults["ga:sessionDuration"]; //Sum of all session durations (in seconds)
        $avgSessionDuration = Analytics::performQuery(Period::days($period), 'ga:avgSessionDuration')->totalsForAllResults["ga:avgSessionDuration"]; //Average session duration (in seconds)
        $traffic = Analytics::fetchTotalVisitorsAndPageViews(Period::days($period));
        foreach($traffic as $item) {
            $visitors[] = $item['visitors'];
            $views[] = $item['pageViews'];
            $date = $item['date']->toFormattedDateString();
            $dates[] = $date;
        }
        $traffic = Charts::multi('bar', 'chartjs')
            // Setup the chart settings
            ->title("Traffic for the last $period days")
            // A dimension of 0 means it will take 100% of the space
            ->dimensions(0, 400) // Width x Height
            ->colors(['#f44256','#ffc107','#4444dd'])
            // Setup the diferent datasets (this is a multi chart)
            ->dataset('Unique Visitors', $visitors)
            ->dataset('Page Views', $views)
            // Setup what the values mean
            ->labels($dates);
        return view('admin.analytics')->with('traffic', $traffic)->with('sessions', $sessions)->with('bounceRate', $bounceRate)->with('totalSessionTime', $totalSessionTime)->with('avgSessionDuration', $avgSessionDuration)->with('period', $period);
    }
}
