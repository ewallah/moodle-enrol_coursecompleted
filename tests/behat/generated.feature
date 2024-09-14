@enrol @ewallah @enrol_coursecompleted
Feature: Enroll users into a course based on the completion of another course
  In order to manage course dependencies
  As an administrator
  I want to ensure users are automatically enrolled into a new course after completing a required course

  Background:
    Given the following "courses" exist:
      | fullname        | shortname | idnumber | enablecompletion |
      | Required Course | reqcourse | req001   | 1                |
      | Target Course   | target    | tar001   | 1                |

    And the following "users" exist:
      | username | firstname | lastname |
      | student1 | Student   | One      |
      | student2 | Student   | Two      |
      | teacher1 | Teacher   | One      |

    And the following "course enrolments" exist:
      | user     | course    | role    |
      | student1 | reqcourse | student |
      | student2 | reqcourse | student |
      | teacher1 | target    | teacher |

    And I log in as "admin"
    And the following "enrol_coursecompleted > courseenrolment" exist:
      | course | required  |
      | target | reqcourse |

    And the following "enrol_coursecompleted > coursecompletion" exist:
      | course    | user     |
      | reqcourse | student1 |
      | target    | student1 |

  Scenario: Admins can see enrolment after course completion
    Given I log in as "admin"
    When I am on the "target" "enrolment methods" page
    Then I should see "After completing course: reqcourse"

  @javascript
  Scenario: Teachers can see enrolment after course completion
    Given I log in as "teacher1"
    And I am on "target" course homepage
    When I navigate to course participants
    Then I should see "Student1"

  @javascript
  Scenario: Verify automatic enrollment after course completion
    Given I am on the "My courses" page logged in as "student1"
    Then I should see "Target Course"

  Scenario: Verify no enrollment without course completion
    Given I am on the "My courses" page logged in as "student2"
    Then I should not see "Target Course"
