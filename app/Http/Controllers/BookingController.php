<?php

/**
 * ausbilder.org - the free course management and planning software.
 * Copyright (C) 2020 Holger Schmermbeck & others (see the AUTHORS file).
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published
 * by the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

namespace App\Http\Controllers;

use App\Company;
use App\Course;
use App\Mail\CourseConfirmation;
use App\Participant;
use Carbon\Carbon;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;
use Vinkla\Hashids\Facades\Hashids;

class BookingController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param  Company  $company
     * @return Company
     */
    public function index(Company $company)
    {
        return $company;
    }

    /**
     * Display a listing of the resource.
     *
     * @param  Company  $company
     * @param $location
     * @return Application|Factory|View
     */
    public function location(Company $company, $location)
    {
        $courses = Course::where([
            ['company_id', $company->id],
            ['location', $location],
            ['start', '>', Carbon::now()],
            ['bookable', 1],
        ])
            ->with('course_types')
            ->with('prices')
            ->with('participants')
            ->orderBy('start')
            ->get();

        return view('booking.location', compact('company', 'courses', 'location'));
    }

    /**
     * Display a listing of the resource.
     *
     * @param  Company  $company
     * @param $location
     * @return Application|Factory|View
     */
    public function secLocation(Company $company, $location)
    {
        $courses = Course::where([
            ['company_id', $company->id],
            ['location', $location],
            ['start', '>', Carbon::now()],
            ['bookable', 1],
        ])
            ->with('course_types')
            ->with('prices')
            ->with('participants')
            ->orderBy('start')
            ->get();

        return view('booking.secLocation', compact('company', 'courses', 'location'));
    }

    /**
     * Display a listing of the resource.
     *
     * @param  Company  $company
     * @param $location
     * @return Application|Factory|View
     */
    public function seminarLocation(Company $company, $location)
    {
        $courses = Course::where([
            ['company_id', $company->id],
            ['seminar_location', $location],
            ['start', '>', Carbon::now()],
            ['bookable', 1],
        ])
            ->with('course_types')
            ->with('prices')
            ->with('participants')
            ->orderBy('start')
            ->get();

        return view('booking.location', compact('company', 'courses', 'location'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @param  Company  $company
     * @param  Course  $course
     * @return Application|Factory|RedirectResponse|View
     */
    public function create(Company $company, $course)
    {
        $course = Course::where([
            ['id', $this->validate_course($course)],
            ['company_id', $company->id],
            ['start', '>', Carbon::now()],
            ['bookable', 1],
        ])
            ->with('prices')
            ->with('participants')
            ->with('course_types')
            ->first();

        if (! $course) {
            return back()->withErrors(
                [
                    'message' => __('The course has already started.'),
                ]
            );
        } elseif (($course->seats - count($course->participants)) <= 0) {
            if (isset($course->location)) {
                $location = $course->location;
            } elseif (isset($course->seminar_location)) {
                $location = $course->seminar_location;
            } else {
                abort(403);
            }

            /** @var TYPE_NAME $location */
            return redirect()->route('booking.location', ['company' => $company, $location])
                ->withErrors(
                [
                    'message' => __('The course is already full.'),
                ]
            );
        }

        return view('booking.create', compact('company', 'course'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @param  Company  $company
     * @param  Course  $course
     * @return Application|Factory|RedirectResponse|View
     */
    public function secCreate(Company $company, $course)
    {
        $course = Course::where([
            ['id', $this->validate_course($course)],
            ['company_id', $company->id],
            ['start', '>', Carbon::now()],
            ['bookable', 1],
        ])
            ->with('prices')
            ->with('participants')
            ->with('course_types')
            ->first();

        if (! $course) {
            return back()->withErrors(
                [
                    'message' => __('The course has already started.'),
                ]
            );
        } elseif (($course->seats - count($course->participants)) <= 0) {
            if (isset($course->location)) {
                $location = $course->location;
            } elseif (isset($course->seminar_location)) {
                $location = $course->seminar_location;
            } else {
                abort(403);
            }

            /** @var TYPE_NAME $location */
            return redirect()->route('booking.location', ['company' => $company, $location])
                ->withErrors(
                    [
                        'message' => __('The course is already full.'),
                    ]
                );
        }

        return view('booking.secCreate', compact('company', 'course'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  Request  $request
     * @param  Company  $company
     * @param $course
     * @return Application|Factory|RedirectResponse|View
     */
    public function store(Request $request, Company $company, $course)
    {
        $course = Course::where([
            ['id', $this->validate_course($course)],
            ['company_id', $company->id],
            ['start', '>', Carbon::now()],
            ['bookable', 1],
        ])
            ->with('prices')
            ->with('participants')
            ->with('course_types')
            ->first();

        if (! $course) {
            return redirect()->route('booking.location', ['company' => $course->location])
                ->withErrors(
                [
                    'message' => __('The course has already started.'),
                ]
            );
        } elseif (($course->seats - count($course->participants)) <= 0) {
            return redirect()->route('booking.location', ['company' => $course->location])
                ->withErrors(
                [
                    'message' => __('The course is already full.'),
                ]
            );
        }

        $this->validate($request, [
            'price' => 'required|integer',
            'lastname' => 'required|min:3',
            'firstname' => 'required|min:3',
            'date_of_birth' => 'required|date',
            'street' => 'required|min:3',
            'number' => 'required',
            'zipcode' => 'required',
            'location' => 'required|min:3',
            'phone' => 'required',
            'email' => 'required|email',
            'terms' => 'accepted',
            'cancellationPolicy' => 'accepted',
            'dataProtection' => 'accepted',
        ]);

        // abort if price id is not for this course
        abort_unless(isset($course->prices->pluck('price', 'id')[$request->price]), 403);

        Participant::create([
            'course_id' => $course->id,
            'lastname' => $request->lastname,
            'firstname' => $request->firstname,
            'date_of_birth' => $request->date_of_birth,
            'street' => $request->street.' '.$request->number,
            'zipcode' => $request->zipcode,
            'location' => $request->location,
            'phone' => $request->phone,
            'email' => $request->email,
            'price' => $course->prices->pluck('price', 'id')[$request->price],
            'price_id' => $request->price,
        ]);

        Mail::to($request->email)
            ->send(new CourseConfirmation($company, $course, $request));

        return view('booking.confirmation', compact('company', 'course'));
    }

    /**
     * send a link to an overview via sms.
     *
     * @param Request $request
     * @param Company $company
     * @param $location
     * @return Application|Factory|View
     */
    public function sendOverview(Request $request, Company $company, $location)
    {
        $url = env('APP_URL').'/booking/'.Hashids::encode($company->id).'/loc/'.$location;

        $text = str_replace(':url', $url, __('Hello, you find our course overview under :url'));

        SmsController::send($request->number, $text);

        return view('booking.smsConfirmation');
    }

    /**
     * send a link to a course via sms.
     *
     * @param Request $request
     * @param Company $company
     * @param Course $course
     * @return Application|Factory|View
     */
    public function sendLink(Request $request, Company $company, Course $course)
    {
        $url = env('APP_URL').'/booking/'.Hashids::encode($company->id).'/'.Hashids::encode($course->id);

        $text = str_replace(':url', $url, __('Hello, you can book your requested course under :url'));

        SmsController::send($request->number, $text);

        return view('booking.smsConfirmation');
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  Request  $request
     * @param  int  $id
     * @return Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return Response
     */
    public function destroy($id)
    {
        //
    }

    private function validate_course($course)
    {
        $course = (new \Vinkla\Hashids\Facades\Hashids)::decode($course);

        // abort if course id is in invalid format
        abort_unless((bool) $course, 404);

        return $course;
    }
}
