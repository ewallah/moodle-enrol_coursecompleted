@enrol @ewallah @enrol_coursecompleted
Feature: Groups kept during enrolment on course completion

  Background:
    Given I mark this test as slow setting a timeout factor of 4
    And the following "courses" exist:
      | fullname | shortname | startdate     | enddate                    | enablecompletion |
      | Course 1 | C1        | ##yesterday## | ##tomorrow##               | 1                |
      | Course 2 | C2        | ##tomorrow##  | ##last day of next month## | 1                |
    And the following "users" exist:
      | username | firstname | lastname |
      | user1    | Username  | 1        |
      | teacher1 | Teacher   | 1        |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | user1    | C1     | student        |
      | teacher1 | C1     | editingteacher |
      | teacher1 | C2     | editingteacher |
    And the following "groups" exist:
      | name    | description       | course | idnumber |
      | Group 1 | Group description | C1     | GROUP1   |
      | Group 1 | Group description | C2     | GROUP2   |
    And the following "group members" exist:
      | user  | group |
      | user1 | GROUP1|

  @javascript
  Scenario: User stays in same group after completing several courses
    When I log in as "admin"
    And I navigate to "Plugins > Enrolments > Manage enrol plugins" in site administration
    And I click on "Disable" "link" in the "Guest access" "table_row"
    And I click on "Disable" "link" in the "Self enrolment" "table_row"
    And I click on "Disable" "link" in the "Cohort sync" "table_row"
    And I click on "Enable" "link" in the "Course completed enrolment" "table_row"
    And I am on "Course 1" course homepage
    And I navigate to "Course completion" in current page administration
    And I expand all fieldsets
    And I set the field "Teacher" to "1"
    And I press "Save changes"
    And I log out

    When I am on the "C2" "Course" page logged in as "teacher1"
    And I am on the "Course 2" "enrolment methods" page
    And I select "Course completed enrolment" from the "Add method" singleselect
    When I set the following fields to these values:
       | Course | Course 1 |
    And I press "Add method"

    When I am on "Course 1" course homepage
    And I navigate to "Reports" in current page administration
    And I click on "Course completion" "link" in the "region-main" "region"
    And I follow "Click to mark user complete"
    And I run the scheduled task "core\task\completion_regular_task"
    And I wait "1" seconds
    And I run the scheduled task "core\task\completion_regular_task"
    And I am on "Course 2" course homepage
    And I navigate to course participants
    Then I should see "Username 1" in the "participants" "table"
    And I should see "Group 1" in the "participants" "table"
