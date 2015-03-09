<?php
class CourseController extends Controller {

	public function index() {
		$sections = Section::with('courses')->get()->toArray();
		$sections = $this->groupCoursesBySemester($sections);

		return View::make('courses.list', [
			'sections'	=> $sections
		]);
	}


	protected function groupCoursesBySemester($sections) {
		$result = [];
		foreach($sections as $i => $section) {
			$courses = $section['courses'];
			unset($section['courses']);

			$data = $section;
			$data['semesters'] = [];

			foreach($courses as $course) {
				$semester = $course['pivot']['semester'];
				if(!isset($data['semesters'][$semester])) {
					$data['semesters'][$semester] = [];
				}
				$data['semesters'][$semester][] = $course;
			}
			ksort($data['semesters']);
			$result[] = $data;
		}

		return $result;
	}

	public function suggestions() {
		$courses = Course::whereHas('sections', function($q) {
			$q->where('string_id', '=', StudentInfo::getSection());
			$q->whereIn('semester', StudentInfo::getLowerSemesters());
		})->get();

		return View::make('courses.suggestions', [
			'courses'	=> $courses
		]);
	}

	public function show($slug, $id) {
		$course = Course::with('reviews.student')
					->findOrFail($id);

		$hasAlreadyReviewed = false;
		if(Tequila::isLoggedIn()) {
			$hasAlreadyReviewed = $course->alreadyReviewedBy(Session::get('student_id'));
		}

		$reviewsPerPage = Config::get('app.nbReviewsPerPage');

		return View::make('courses.show', [
			'course' => $course,
			'slug' 	=> $slug,
			'reviews' => $course->reviews()->paginate($reviewsPerPage),
			'hasAlreadyReviewed' => $hasAlreadyReviewed,
			'nbReviews' => count($course->reviews),
			'isLoggedIn' => Tequila::isLoggedIn()
		]);
	}

	public function createReview($slug, $courseId) {
		$validator = Validator::make(Input::all(), Review::rules());
		$goToCourse = Redirect::action('CourseController@show', [$slug, $courseId]);
		if ($validator->fails()) {
			return $goToCourse
					->withInput()
					->withErrors($validator);
		}

		// Get course and student info
		$course = Course::findOrFail($courseId); // Fails if the course doesn't exist
		$studentId = Session::get('student_id');

		// Check if the course was not already reviewed by the student
		if($course->alreadyReviewedBy($studentId)) {
			return $goToCourse->with('message', ['danger', 'You can\'t review a course twice. Nice try!']);
		}

		// Create the review
		$data = ['course_id' => intval($courseId), 'student_id' => $studentId];

		$newReview = new Review(Input::all());
		$newReview->course_id = $courseId;
		$newReview->student_id = $studentId;
		$newReview->updateAverage();

		if(Input::get('anonymous') == true) {
			$newReview->is_anonymous = 1;
		}

		$newReview->save();

		Event::fire('course.newReview', [$course]);

		return $goToCourse->with('message', ['success', 'Your review was successfuly posted. Thank you!']);
	}
}