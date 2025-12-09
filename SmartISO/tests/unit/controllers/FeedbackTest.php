<?php

use CodeIgniter\Test\CIUnitTestCase;

class FeedbackTest extends CIUnitTestCase
{
    public function testFeedbackControllerExists()
    {
        $classname = '\\App\\Controllers\\Feedback';
        $this->assertTrue(class_exists($classname));
        $ref = new ReflectionClass($classname);
        $this->assertIsObject($ref->newInstanceWithoutConstructor());
    }

    public function testIndexReturnsViewForRequestor()
    {
        $ref = new ReflectionClass('\\App\\Controllers\\Feedback');
        $controller = $ref->newInstanceWithoutConstructor();

        $feedbackStub = new class {
            public function getFeedbackWithDetails($userId = null) { return []; }
            public function getAverageRatings() { return []; }
            public function hasFeedback($submissionId, $userId) { return false; }
        };

        $submissionStub = new class {
            public function where($a, $b = null) { return $this; }
            public function findAll() { return []; }
        };

        $prop = $ref->getProperty('feedbackModel');
        $prop->setAccessible(true);
        $prop->setValue($controller, $feedbackStub);

        $prop2 = $ref->getProperty('submissionModel');
        $prop2->setAccessible(true);
        $prop2->setValue($controller, $submissionStub);

        $_SESSION['user_id'] = 5;
        $_SESSION['user_type'] = 'requestor';

        $result = $controller->index();
        $this->assertIsString($result);
        $this->assertStringContainsString('Feedback', $result);

        unset($_SESSION['user_id']);
        unset($_SESSION['user_type']);
    }

    public function testCreateViewContainsStarRatingMarkup()
    {
        $file = APPPATH . 'Views/feedback/create.php';
        $this->assertFileExists($file);
        $contents = file_get_contents($file);

        $this->assertStringContainsString('rating-stars', $contents, 'Feedback create view should include star rating UI');
        $this->assertStringContainsString('fa-star', $contents, 'Feedback create view should include star icons');
    }

    public function testMarkReviewedReturnsJsonOnMissingFeedback()
    {
        $ref = new ReflectionClass('\\App\\Controllers\\Feedback');
        $controller = $ref->newInstanceWithoutConstructor();

        $feedbackStub = new class {
            public function find($id) { return null; }
            public function update($id, $data) { return false; }
        };

        $responseStub = new class {
            public $last = null;
            public function setJSON($data) { $this->last = $data; return $data; }
        };

        $prop = $ref->getProperty('feedbackModel');
        $prop->setAccessible(true);
        $prop->setValue($controller, $feedbackStub);

        $propResp = $ref->getProperty('response');
        $propResp->setAccessible(true);
        $propResp->setValue($controller, $responseStub);

        $result = $controller->markReviewed(123);
        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
        $this->assertFalse((bool)$result['success']);
    }
}
