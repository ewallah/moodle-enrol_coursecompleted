@enrol @ewallah @enrol_coursecompleted @javascript
Feature: Groups kept during enrolment on course completion

  Background:
    Given the following "courses" exist:
      | fullname | shortname | numsections | startdate | enddate | enablecompletion |
      | Course 1 | C1 | 1 | ##yesterday## | ##tomorrow##| 1 |
      | Course 2 | C2 | 1 | ##tomorrow## | ##last day of next month## | 1 |
      | Course 3 | C3 | 1 | ##tomorrow## | ##last day of next month## | 1 |
      | Course 4 | C4 | 1 | ##tomorrow## | ##last day of next month## | 1 |
    And the following "users" exist:
      | username | firstname | lastname |
      | user1 | Username | 1 |
      | user2 | Username | 2 |
      | teacher1 | Teacher | 1 |
    And the following "course enrolments" exist:
      | user | course | role |
      | user1 | C1 | student |
      | user2 | C1 | student |
      | teacher1| C1 | editingteacher |
      | teacher1| C2 | editingteacher |
      | teacher1| C3 | editingteacher |
      | teacher1| C4 | editingteacher |
    And the following "groups" exist:
      | name | description | course | idnumber |
      | Group 1 | Group description | C1 | GROUP1 |
      | Group 1 | Group description | C2 | GROUP2 |
      | Group 1 | Group description | C3 | GROUP3 |
      | Group 1 | Group description | C4 | GROUP4 |
    And the following "group members" exist:
      | user  | group |
      | user1 | GROUP1|
    And I log in as "admin"
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
    And I am on "Course 2" course homepage
    And I navigate to "Users > Enrolment methods" in current page administration
    And I select "Course completed enrolment" from the "Add method" singleselect

  Scenario: User stays in same group after completing several courses
    When I set the following fields to these values:
       | Course | Course 1 |
    And I press "Add method"
    And I am on "Course 2" course homepage
    And I navigate to "Course completion" in current page administration
    And I expand all fieldsets
    And I set the field "Teacher" to "1"
    And I press "Save changes"
    And I am on "Course 3" course homepage
    And I navigate to "Course completion" in current page administration
    And I expand all fieldsets
    And I set the field "Teacher" to "1"
    And I press "Save changes"
    And I am on "Course 3" course homepage
    And I navigate to "Users > Enrolment methods" in current page administration
    And I select "Course completed enrolment" from the "Add method" singleselect
    And I set the following fields to these values:
       | Course | Course 2 |
    And I press "Add method"
    And I am on "Course 4" course homepage
    And I navigate to "Users > Enrolment methods" in current page administration
    And I select "Course completed enrolment" from the "Add method" singleselect
    And I set the following fields to these values:
       | Course | Course 3 |
    And I press "Add method"
    And I log out
    When I am on the "C1" "Course" page logged in as "teacher1"
    And I navigate to "Reports > Course completion" in current page administration
    And I follow "Click to mark user complete"
    And I run the scheduled task "core\task\completion_regular_task"
    And I wait "1" seconds
    And I run the scheduled task "core\task\completion_regular_task"
    And I am on "Course 2" course homepage
    And I navigate to course participants
    Then I should see "Username 1" in the "participants" "table"
    And I should see "Group 1" in the "participants" "table"
    And I am on "Course 2" course homepage
    And I navigate to "Reports > Course completion" in current page administration
    And I follow "Click to mark user complete"
    And I run the scheduled task "core\task\completion_regular_task"
    And I wait "1" seconds
    And I run the scheduled task "core\task\completion_regular_task"
    And I am on "Course 2" course homepage
    And I navigate to "Reports > Course completion" in current page administration
    And I am on "Course 3" course homepage
    And I navigate to course participants
    Then I should see "Username 1" in the "participants" "table"
    And I should see "Group 1" in the "participants" "table"
    And I am on "Course 3" course homepage
    And I navigate to "Reports > Course completion" in current page administration
    And I follow "Click to mark user complete"
    And I run the scheduled task "core\task\completion_regular_task"
    And I run all adhoc tasks
    And I trigger cron
    And I run the scheduled task "core\task\completion_regular_task"
    And I run all adhoc tasks
    And I trigger cron
    And I am on "Course 4" course homepage
    And I navigate to course participants
    Then I should see "Username 1" in the "participants" "table"
    And I should see "Group 1" in the "participants" "table"
