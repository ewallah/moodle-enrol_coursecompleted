@enrol @ewallah @enrol_coursecompleted @javascript
Feature: Restrict enrolment on course completion to selected source-course groups
  In order to only enrol part of the students completing a course
  As a teacher
  I need to restrict the automatic enrolment to members of chosen groups

  Background:
    Given the following "courses" exist:
      | fullname | shortname | startdate     | enddate                    | enablecompletion |
      | Course 1 | C1        | ##yesterday## | ##tomorrow##               | 1                |
      | Course 2 | C2        | ##tomorrow##  | ##last day of next month## | 1                |
    And the following "users" exist:
      | username |
      | user1    |
      | user2    |
      | teacher1 |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | user1    | C1     | student        |
      | user2    | C1     | student        |
      | teacher1 | C1     | editingteacher |
      | teacher1 | C2     | editingteacher |
    And the following "groups" exist:
      | name    | description       | course | idnumber |
      | Group 1 | Group description | C1     | GROUP1   |
    And the following "group members" exist:
      | user  | group  |
      | user1 | GROUP1 |
    And I log in as "admin"
    And I am on "Course 1" course homepage
    And I navigate to "Course completion" in current page administration
    And I expand all fieldsets
    And I set the field "Teacher" to "1"
    And I press "Save changes"
    And I add "Course completed enrolment" enrolment method in "Course 2" with:
      | Course             | Course 1 |
      | Restrict to groups | Group 1  |
    And I log out

  Scenario: Only members of the selected group are enrolled, and only they see the promise
    # user1 is in Group 1, user2 is not.
    When I am on the "C2" "Course" page logged in as "user1"
    Then I should see "You will be enrolled in this course when you complete course"
    And I log out
    And I am on the "C2" "Course" page logged in as "user2"
    And I should not see "You will be enrolled in this course when you complete course"
    And I log out

    # Mark both students complete in Course 1.
    And I am on the "C1" "Course" page logged in as "teacher1"
    And I navigate to "Reports" in current page administration
    And I click on "Course completion" "link" in the "region-main" "region"
    And I follow "Click to mark user complete"
    And I follow "Click to mark user complete"
    And I wait "1" seconds
    And I run the scheduled task "core\task\completion_regular_task"

    # Only the group member ends up enrolled in Course 2.
    And I am on "Course 2" course homepage
    And I navigate to course participants
    Then I should see "1 participants found"
