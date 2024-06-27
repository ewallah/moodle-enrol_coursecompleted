@enrol @ewallah @enrol_coursecompleted @javascript
Feature: Enrolment on course completion

  Background:
    Given the following "courses" exist:
      | fullname | shortname | startdate     | enddate                    | enablecompletion |
      | Course 1 | C1        | ##yesterday## | ##tomorrow##               | 1                |
      | Course 2 | C2        | ##tomorrow##  | ##last day of next month## | 1                |
    And the following "activities" exist:
      | activity   | name   | intro            | course | idnumber |
      | page       | Page A | page description | C1     | page1    |
      | page       | Page B | page description | C2     | page2    |
    And the following "users" exist:
      | username | firstname | lastname | timezone            |
      | user1    | Username  | 1        | Asia/Tokyo          |
      | user2    | Username  | 2        | Europe/Brussels     |
      | teacher1 | Teacher   | 1        | America/Mexico_city |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | user1    | C1     | student        |
      | user2    | C1     | student        |
      | teacher1 | C1     | editingteacher |
      | teacher1 | C2     | editingteacher |
    And the following config values are set as admin:
      | expiredaction | Unenrol user from course | enrol_coursecompleted |
    And the following config values are set as admin:
      | enableasyncbackup | 0 |
    And I log in as "admin"
    And I navigate to "Location > Location settings" in site administration
    And I set the field "Default timezone" to "Europe/Brussels"
    And I am on "Course 1" course homepage
    And I navigate to "Course completion" in current page administration
    And I expand all fieldsets
    And I set the field "Teacher" to "1"
    And I press "Save changes"
    And I am on the "Course 2" "enrolment methods" page
    And I select "Course completed enrolment" from the "Add method" singleselect

  Scenario: When a course is completed, a user is automatically enrolled into another course
    Given I set the following fields to these values:
       | Course | Course 1 |
    And I press "Add method"
    And I am on "Course 2" course homepage
    And I log out
    And I am on the "C1" "Course" page logged in as "teacher1"
    And I navigate to "Reports" in current page administration
    And I click on "Course completion" "link" in the "region-main" "region"
    And I follow "Click to mark user complete"
    And I log out
    And I wait "1" seconds
    And I run the scheduled task "core\task\completion_regular_task"
    When I am on the "C1" "Course" page logged in as "user1"
    Then I should not see "You will be enrolled in this course when"
    And I should see "Page A"
    And I am on "Course 2" course homepage
    But I should not see "You will be enrolled in this course when"
    And I should see "Page B"

  Scenario: Course completed enrolment fields
    Given I set the following fields to these values:
       | Course                    | Course 1 |
       | id_enrolperiod_enabled    | 1        |
       | id_enrolperiod_number     | 3 days   |
       | id_enrolstartdate_enabled | 1        |
       | id_enrolstartdate_year    | 2030     |
       | id_enrolenddate_enabled   | 1        |
       | id_enrolenddate_year      | 2031     |
    And I press "Add method"
    And I am on "Course 2" course homepage
    And I log out
    When I am on the "C1" "Course" page logged in as "teacher1"
    And I navigate to "Reports" in current page administration
    And I click on "Course completion" "link" in the "region-main" "region"
    And I follow "Click to mark user complete"
    And I wait "1" seconds
    And I run the scheduled task "core\task\completion_regular_task"
    And I am on "Course 2" course homepage
    Then I navigate to course participants
    # The user enrolment only starts in 2030
    But I should not see "user1"

  Scenario: Course completed enrolment with a later start date
    Given I set the following fields to these values:
       | Course                    | Course 1 |
       | id_enrolstartdate_enabled | 1        |
       | id_enrolstartdate_year    | 2030     |
    And I press "Add method"
    And I am on "Course 2" course homepage
    And I log out
    And I am on the "C1" "Course" page logged in as "teacher1"
    And I navigate to "Reports" in current page administration
    And I click on "Course completion" "link" in the "region-main" "region"
    And I follow "Click to mark user complete"
    And I wait "1" seconds
    And I run the scheduled task "core\task\completion_regular_task"
    And I am on "Course 2" course homepage
    When I navigate to course participants
    # The user enrolment only starts in 2030
    Then I should not see "user1"
    And I log out
    And I log in as "guest"
    And I am on course index
    And I follow "Course 2"
    # The user enrolment only starts in 2030
    But I should not see "You will be enrolled in this course when you complete course"

  Scenario: Manage enrolled users
    Given I set the following fields to these values:
       | Course | Course 1 |
    And I press "Add method"
    And I am on "Course 2" course homepage
    And I log out
    And I am on the "C1" "Course" page logged in as "teacher1"
    And I navigate to "Reports" in current page administration
    And I click on "Course completion" "link" in the "region-main" "region"
    And I follow "Click to mark user complete"
    And I log out
    And I wait "1" seconds
    And I run the scheduled task "core\task\completion_regular_task"
    And I am on the "C2" "Course" page logged in as "teacher1"
    And I navigate to course participants
    And I should see "Username 1" in the "participants" "table"
    And I log out
    And I am on the "C2" "Course" page logged in as "admin"
    And I navigate to course participants
    And I click on "//a[@data-action='unenrol']" "xpath_element" in the "user1" "table_row"
    And I click on "Unenrol" "button" in the "Unenrol" "dialogue"
    And I click on "//a[@data-action='unenrol']" "xpath_element" in the "teacher1" "table_row"
    And I click on "Unenrol" "button" in the "Unenrol" "dialogue"
    And I am on the "Course 2" "enrolment methods" page
    # And I wait until the page is ready
    When I click on "[aria-label='Enrol users']" "css_element" in the "tr.lastrow" "css_element"
    Then I should see "Username 1"
    And I press "Enrol users"
    And I should see "1 Users enrolled"
    And I am on "Course 2" course homepage
    And I navigate to course participants
    And I should see "Username 1" in the "participants" "table"
    And I should see "Course 2"
    And I click on "[title='Course completion']" "css_element"
    And I should see "Course 1"
    And I should see "Aggregation method"

  Scenario: Bulk unenrol users
    Given I set the following fields to these values:
       | Course | Course 1 |
    And I press "Add method"
    And I am on "Course 2" course homepage
    And I log out
    When I am on the "C1" "Course" page logged in as "teacher1"
    And I navigate to "Reports" in current page administration
    And I click on "Course completion" "link" in the "region-main" "region"
    And I follow "Click to mark user complete"
    And I log out
    And I wait "1" seconds
    And I run the scheduled task "core\task\completion_regular_task"
    And I log in as "admin"
    And I am on "Course 2" course homepage
    And I navigate to course participants
    And I click on "Select all" "checkbox"
    And I set the field "With selected users..." to "Delete selected enrolments on course completion"
    Then I should see "Delete selected enrolments on course completion"
    And I press "Unenrol users"
    But I should not see "Username 1" in the "participants" "table"

  Scenario: Bulk edit users
    Given I set the following fields to these values:
       | Course | Course 1 |
    And I press "Add method"
    And I am on "Course 2" course homepage
    And I log out
    And I am on the "C1" "Course" page logged in as "teacher1"
    And I navigate to "Reports" in current page administration
    And I click on "Course completion" "link" in the "region-main" "region"
    And I follow "Click to mark user complete"
    And I log out
    And I wait "1" seconds
    And I run the scheduled task "core\task\completion_regular_task"
    And I am on the "C2" "Course" page logged in as "admin"
    And I navigate to course participants
    When I click on "Select 'Username 1'" "checkbox"
    And I set the field "With selected users..." to "Edit selected enrolments on course completion"
    Then I should see "Edit selected enrolments on course completion"
    And I set the field "Alter status" to "Suspended"
    And I press "Save changes"
    And I should see "Username 1" in the "participants" "table"
    And I should see "Suspended" in the "participants" "table"

  @javascript
  Scenario: Admin can backup and restore a course with enrol course completions
    Given I set the following fields to these values:
       | Course | Course 1 |
    And I press "Add method"
    And I am on "Course 2" course homepage
    And I log out
    And I log in as "admin"
    When I am on "Course 2" course homepage
    And I backup "Course 2" course using this options:
      | Confirmation | Filename | test_backup.mbz |
    And I restore "test_backup.mbz" backup into a new course using this options:
      | Schema | Course name       | Course 3 |
      | Schema | Course short name | C3       |
    And I am on "Course 3" course homepage
    And I navigate to course participants
    Then I should see "1 participants found"
    But I should not see "Username 1" in the "participants" "table"
    And I should not see "Username 2" in the "participants" "table"
    And I am on the "Course 3" "enrolment methods" page
    And I should see "After completing course: C1"
