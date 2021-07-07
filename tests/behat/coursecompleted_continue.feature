@enrol @ewallah @enrol_coursecompleted @javascript
Feature: Continue button in enrolment on course completion

  Background:
    Given the following "courses" exist:
      | fullname | shortname | enablecompletion | summary |
      | Course 1 | C1 | 1 | AAA |
      | Course 2 | C2 | 1 | BBB |
      | Course 3 | C3 | 1 | CCC |
      | Course 4 | C4 | 1 | DDD |
    And the following "users" exist:
      | username | firstname | lastname |
      | user1 | Username | 1 |
    And the following "course enrolments" exist:
      | user | course | role |
      | user1 | C1 | student |
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
    And I set the following fields to these values:
       | Course     | Course 1 |
    And I press "Add method"
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

  Scenario: Learning path visible
    Given the following config values are set as admin:
      | svglearnpath | 1 | enrol_coursecompleted |
    And I am on the "C2" "Course" page logged in as "user1"
    Then I should see "You will be enrolled in this course when"
    And ".fa-stack.fa-2x" "css_element" should exist
    When I am on the "C3" "Course" page
    Then I should see "You will be enrolled in this course when"
    And ".fa-stack.fa-2x" "css_element" should exist
    When I am on the "C4" "Course" page
    Then I should see "You will be enrolled in this course when"
    And ".fa-stack.fa-2x" "css_element" should exist
    When I click on ".fa-stack.fa-2x" "css_element"
    Then I should not see "DDD"

  Scenario: Learning path hidden
    Given the following config values are set as admin:
      | svglearnpath | 0 | enrol_coursecompleted |
    And I am on the "C2" "Course" page logged in as "user1"
    Then I should see "You will be enrolled in this course when"
    And ".fa-stack.fa-2x" "css_element" should not exist

  Scenario: Continue button visible
    Given the following config values are set as admin:
      | svglearnpath       | 1 | enrol_coursecompleted |
      | showcontinuebutton | 1 | enrol_coursecompleted |
    And I am on the "C2" "Course" page logged in as "user1"
    Then I should see "You will be enrolled in this course when"
    And I should see "Continue"
    When I press "Continue"
    Then I should see "You are already logged in"
    And I press "Cancel"
    Then I should see "Course overview"
