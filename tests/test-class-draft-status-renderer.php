<?php

class Test_DraftStatusRenderer extends WP_UnitTestCase {

    protected $renderer;

    public function setUp(): void {
        parent::setUp();
        $this->renderer = new DraftStatus();
    }

    /**
     * Helper to call protected methods for testing purposes.
     */
    protected function call_protected_method($obj, $name, array $args) {
        $class = new \ReflectionClass($obj);
        $method = $class->getMethod($name);
        $method->setAccessible(true);
        return $method->invokeArgs($obj, $args);
    }

    /**
     * @dataProvider provide_due_dates
     */
    public function test_getDueDateDisplay($days_from_now, $expected_class_part, $expected_label_part) {
        // Set the WordPress timezone to UTC for consistent testing
        update_option('timezone_string', 'UTC');

        $date_string = 'today';
        if ($days_from_now !== 0) {
            $date_string = ($days_from_now > 0 ? '+' : '') . $days_from_now . ' days';
        }
        $due_date = date('Y-m-d', strtotime($date_string));

        $result = $this->call_protected_method($this->renderer, 'getDueDateDisplay', [$due_date]);

        $this->assertStringContainsString($expected_class_part, $result['class']);
        $this->assertStringContainsString($expected_label_part, $result['label']);
    }

    public function provide_due_dates() {
        return [
            'Overdue' => [-5, 'draft-due-overdue', 'Overdue:'],
            'Due Today' => [0, 'draft-due-today', 'Due today'],
            'Due in 1 day' => [1, 'draft-due-soon', 'Due:'],
            'Due in 3 days' => [3, 'draft-due-soon', 'Due:'],
            'Due in 4 days' => [4, 'draft-due-date', 'Due:'], // Not 'soon' anymore
            'Due in 30 days' => [30, 'draft-due-date', 'Due:'],
        ];
    }
}
