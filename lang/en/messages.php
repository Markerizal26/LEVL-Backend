<?php

return [
  // HTTP status messages
  "success" => "Success",
  "created" => "Created successfully",
  "updated" => "Updated successfully",
  "deleted" => "Deleted successfully",
  "saved" => "Saved successfully",
  "published" => "Published successfully",
  "unpublished" => "Unpublished successfully",
  "archived" => "Archived successfully",
  "restored" => "Restored successfully",
  "approved" => "Approved successfully",
  "rejected" => "Rejected successfully",
  "sent" => "Sent successfully",
  "data_retrieved" => "Data retrieved successfully.",
  "error" => "An error occurred.",
  "not_found" => "Resource not found.",
  "unauthorized" => "Unauthorized access.",
  "unauthenticated" => "Your session has expired. Please login again.",
  "forbidden" => "Forbidden.",
  "validation_error" => "Validation failed.",
  "validation_failed" => "The given data was invalid.",
  "server_error" => "Internal server error.",
  "bad_request" => "Bad request.",
  "conflict" => "Your request conflicts with the current state of the resource.",
  "gone" => "The resource you requested has been permanently deleted.",
  "session_expired" => "Your session has expired. Please login again.",
  "session_invalid" => "Invalid session. Please login again.",
  "session_blacklisted" => "Session blacklisted. Please login again.",
  "session_not_found" => "Session not found. Please login again.",
  "user_data_not_found" => "User data not found.",
  "invalid_credentials" => "Invalid credentials.",

  // Common Module
  "categories" => [
    "list_retrieved" => "Category list retrieved successfully.",
    "created" => "Category created successfully.",
    "updated" => "Category updated successfully.",
    "deleted" => "Category deleted successfully.",
    "not_found" => "Category not found.",
  ],

  // Tags Module
  "tags" => [
    "created" => "Tag created successfully.",
    "updated" => "Tag updated successfully.",
    "deleted" => "Tag deleted successfully.",
    "not_found" => "Tag not found.",
    "list_retrieved" => "Tag list retrieved successfully.",
  ],

  // Units Module
  "units" => [
    "created" => "Unit created successfully.",
    "updated" => "Unit updated successfully.",
    "deleted" => "Unit deleted successfully.",
    "published" => "Unit published successfully.",
    "unpublished" => "Unit unpublished successfully.",
    "reordered" => "Unit order updated successfully.",
    "order_updated" => "Unit order updated successfully.",
    "not_found" => "Unit not found.",
    "not_in_course" => "Unit not found in this course.",
    "no_create_access" => "You do not have access to create units for this course.",
    "no_update_access" => "You do not have access to update this unit.",
    "no_delete_access" => "You do not have access to delete this unit.",
    "no_publish_access" => "You do not have access to publish this unit.",
    "no_unpublish_access" => "You do not have access to unpublish this unit.",
    "no_reorder_access" => "You do not have access to reorder units for this course.",
    "some_not_found" => "Some units not found in this course.",
  ],

  // Lessons Module
  "lessons" => [
    "created" => "Lesson created successfully.",
    "updated" => "Lesson updated successfully.",
    "deleted" => "Lesson deleted successfully.",
    "published" => "Lesson published successfully.",
    "unpublished" => "Lesson unpublished successfully.",
    "not_found" => "Lesson not found.",
    "not_in_unit" => "Lesson not found in this unit.",
    "no_view_list_access" => "You do not have access to view lesson list.",
    "no_view_access" => "You do not have access to view this lesson.",
    "no_create_access" => "You do not have access to create lessons for this unit.",
    "no_update_access" => "You do not have access to update this lesson.",
    "no_delete_access" => "You do not have access to delete this lesson.",
    "no_publish_access" => "You do not have access to publish this lesson.",
    "no_unpublish_access" => "You do not have access to unpublish this lesson.",
    "not_enrolled" => "You must be enrolled to access this lesson.",
    "locked_prerequisite" => "This lesson is locked. Complete prerequisite lessons first.",
    "unavailable" => "Lesson is not yet available.",
  ],

  // Questions Module
  "questions" => [
    "created" => "Question created successfully.",
    "updated" => "Question updated successfully.",
    "deleted" => "Question deleted successfully.",
    "not_found" => "Question not found.",
  ],

  // Lesson Blocks Module
  "lesson_blocks" => [
    "created" => "Lesson block created successfully.",
    "updated" => "Lesson block updated successfully.",
    "deleted" => "Lesson block deleted successfully.",
    "not_found" => "Lesson block not found.",
    "lesson_not_in_course" => "Lesson not found in this course.",
    "course_not_found" => "Course not found.",
    "no_view_access" => "You do not have access to view this lesson block.",
    "no_manage_access" => "You do not have access to manage lesson blocks for this lesson.",
    "no_update_access" => "You do not have access to update this lesson block.",
    "no_delete_access" => "You do not have access to delete this lesson block.",
  ],

  "common" => [
    "master_data_retrieved" => "Master data retrieved successfully.",
    "not_found" => "Data not found.",
  ],

  "master_data" => [
    "types_retrieved" => "Master data types retrieved successfully.",
    "roles_retrieved" => "Roles retrieved successfully.",
    "user_statuses" => "User status list",
    "course_statuses" => "Course status list",
    "course_types" => "Course type list",
    "enrollment_types" => "Enrollment type list",
    "level_tags" => "Difficulty level list",
    "progression_modes" => "Progression mode list",
    "content_types" => "Content type list",
    "enrollment_statuses" => "Enrollment status list",
    "progress_statuses" => "Progress status list",
    "assignment_statuses" => "Assignment status list",
    "submission_statuses" => "Submission status list",
    "submission_types" => "Submission type list",
    "content_statuses" => "Content status list",
    "priorities" => "Priority list",
    "target_types" => "Target type list",
    "challenge_types" => "Challenge type list",
    "challenge_assignment_statuses" => "User challenge status list",
    "challenge_criteria_types" => "Challenge criteria type list",
    "badge_types" => "Badge type list",
    "point_source_types" => "Point source list",
    "point_reasons" => "Point reason list",
    "notification_types" => "Notification type list",
    "notification_channels" => "Notification channel list",
    "notification_frequencies" => "Notification frequency list",
    "grade_statuses" => "Grade status list",
    "grade_source_types" => "Grade source list",
    "category_statuses" => "Category status list",
    "setting_types" => "Setting type list",
  ],

  // Courses Module
  "courses" => [
    "created" => "Course created successfully.",
    "updated" => "Course updated successfully.",
    "deleted" => "Course deleted successfully.",
    "published" => "Course published successfully.",
    "unpublished" => "Course unpublished successfully.",
    "enrollment_settings_updated" => "Enrollment settings updated successfully.",
    "not_found" => "Course not found.",
    "no_unpublish_access" => "You do not have access to unpublish this course.",
    "no_update_key_access" => "You do not have access to update enrollment key for this course.",
    "no_remove_key_access" => "You do not have access to remove enrollment key for this course.",
    "no_generate_key_access" =>
      "You do not have access to generate enrollment key for this course.",
    "key_generated" => "Enrollment key generated successfully.",
    "key_removed" => "Enrollment key removed and enrollment type changed to auto_accept.",
    "code_exists" => "Code already in use.",
    "slug_exists" => "Slug already in use.",
    "duplicate_data" => "Duplicate data. Please check your input.",
  ],

  // Auth Module
  "auth" => [
    "login_success" => "Login successful.",
    "logout_success" => "Logout successful.",
    "register_success" => "Registration successful. Please check your email for verification.",
    "user_created_success" => "User created successfully.",
    "invalid_credentials" => "Invalid credentials.",
    "account_inactive" => "Your account is inactive.",
    "account_suspended" => "Your account has been suspended.",
    "email_not_verified" => "Please verify your email first.",
    "google_oauth_failed" => "Unable to initiate Google OAuth. Please login manually.",
    "email_already_verified" => "Your email is already verified.",
    "verification_sent" =>
      "Verification link has been sent to your email. Valid for 3 minutes and can only be used once.",
    "email_change_sent" => "Email change verification link has been sent. Valid for 3 minutes.",
    "email_changed" => "Email successfully changed and verified.",
    "verification_expired" => "Verification code has expired.",
    "verification_invalid" => "Verification code is incorrect.",
    "verification_invalid_or_token" => "Verification code is incorrect or token is invalid.",
    "email_taken" => "Email is already used by another account.",
    "verification_not_found" => "Verification link not found.",
    "verification_failed" => "Verification failed.",
    "email_verified" => "Your email has been verified successfully.",
    "link_expired" => "Verification link has expired.",
    "link_invalid" => "Verification link is invalid or already used.",
    "link_not_found" => "Verification link not found.",
    "email_change_not_found" => "Verification link for email change not found.",
    "email_change_invalid" => "Verification link for email change is invalid or already used.",
    "email_change_expired" => "Verification link for email change has expired.",
    "email_change_success" => "Your email has been successfully changed.",
    "credentials_resent" => "Credentials successfully resent.",
    "user_not_found" => "User not found",
    "admin_only" => "Only for Admin, Superadmin, or Instructor accounts with pending status.",
    "status_updated" => "User status updated successfully.",
    "student_creation_forbidden" => "Student creation is forbidden via Admin API. Students must register themselves.",
    "status_cannot_be_pending" => "Changing status to pending is not allowed.",
    "status_cannot_be_changed_from_pending" => "User status that is still 'pending' cannot be changed manually.",
    "password_changed" => "Password changed successfully.",
    "password_set_success" => "Password set successfully.",
    "password_already_set" => "Password has already been set.",
    "avatar_deleted" => "Avatar deleted successfully.",
    "username_already_set" => "Username is already set for your account.",
    "username_set_success" => "Username successfully set.",
    "cannot_deactivate_self" => "You cannot deactivate your own account.",
    "cannot_delete_self" => "You cannot delete your own account.",
    "no_access_to_user" => "You do not have access to view this user.",
    "current_password_incorrect" => "Current password is incorrect.",
    "password_min_length" => "New password must be at least 8 characters long.",
    "password_incorrect" => "Password is incorrect.",
    "profile_retrieved" => "Profile retrieved successfully.",
    "refresh_success" => "Token refreshed successfully.",
    "email_not_verified" => "Email not verified. Please verify your email first.",
    "middleware_refresh_only" => "This middleware is only for refresh endpoint.",
    "refresh_token_required" => "Refresh token is required.",
    "refresh_token_invalid" => "Refresh token is invalid or expired.",
    "account_not_active" => "Account is not active.",
    "avatar_upload_failed" => "Avatar failed to upload. Please ensure the file is an image (JPG, PNG, GIF) and size does not exceed 2MB.",
    "avatar_single_file" => "Only 1 avatar can be uploaded.",
    "deletion_request_sent" => "Account deletion confirmation link has been sent to your email.",
    "account_deleted_success" => "Your account has been successfully deleted.",
    "deletion_failed" => "Failed to delete account. Invalid or expired confirmation link.",
    "bulk_export_queued" => "User export is being processed and will be sent to your email.",
  ],

  "account" => [
    "restore_success" => "Account successfully restored.",
    "deletion_in_progress" => "Your account is already in the deletion process.",
    "restore_not_deleted" => "Only accounts with deleted status can be restored.",
    "restore_expired" => "Account restoration grace period (:days days) has expired.",
    "cleanup_success" => "Successfully permanently deleted :count accounts.",
  ],

  // User Module
  "user" => [
    "not_found" => "User not found.",
  ],

  // Password Module
  "password" => [
    "reset_sent" => "Password reset link has been sent to your email.",
    "reset_success" => "Password reset successfully.",
    "invalid_reset_token" => "Invalid reset token.",
    "expired_reset_token" => "Reset token has expired.",
    "user_not_found" => "User not found.",
    "unauthorized" => "Unauthorized.",
    "old_password_mismatch" => "Old password is incorrect.",
    "updated" => "Password updated successfully.",
    "current_required" => "Current password is required.",
    "new_required" => "New password is required.",
    "min_length" => "New password must be at least 8 characters.",
    "confirmation_mismatch" => "Password confirmation does not match.",
    "strength_requirements" => "Password must contain at least one uppercase letter, one lowercase letter, one number, and one special character (@$!%*?&#).",
    "token_invalid" => "Reset token is invalid.",
    "token_expired" => "Reset token has expired.",
  ],

  // Profile Module
  "profile" => [
    "updated" => "Profile updated successfully.",
    "achievement_retrieved" => "Achievements retrieved successfully.",
    "activity_retrieved" => "Activity log retrieved successfully.",
    "privacy_updated" => "Privacy settings updated successfully.",
    "statistics_retrieved" => "Statistics retrieved successfully.",
    "not_found" => "Profile not found.",
    "account_updated" => "Account information updated successfully.",
    "account_deleted" => "Account deleted successfully. You have 30 days to recover it.",
    "updated_success" => "Profile updated successfully.",
    "suspended_success" => "Account suspended successfully.",
    "activated_success" => "Account activated successfully.",
    "no_permission" => "You do not have permission to view this profile.",
  ],

  // Achievement Module
  "achievement" => [
    "badge_not_owned" => "You do not have this badge.",
    "badge_not_pinned" => "Badge is not pinned.",
  ],

  // Announcements Module
  "announcements" => [
    "created" => "Announcement created successfully.",
    "updated" => "Announcement updated successfully.",
    "deleted" => "Announcement deleted successfully.",
    "published" => "Announcement published successfully.",
    "scheduled" => "Announcement scheduled successfully.",
    "not_found" => "Announcement not found.",
    "list_retrieved" => "Announcement list retrieved successfully.",
    "marked_read" => "Announcement marked as read.",
  ],

  // News Module
  "news" => [
    "created" => "News created successfully.",
    "updated" => "News updated successfully.",
    "deleted" => "News deleted successfully.",
    "published" => "News published successfully.",
    "scheduled" => "News scheduled successfully.",
    "not_found" => "News not found.",
    "list_retrieved" => "News list retrieved successfully.",
  ],

  // Enrollments Module
  "enrollments" => [
    "enrolled" => "Successfully enrolled in course.",
    "unenrolled" => "Successfully unenrolled from course.",
    "completed" => "Course completed successfully.",
    "already_enrolled" => "Already enrolled in this course.",
    "not_enrolled" => "Not enrolled in this course.",
    "cancelled" => "Enrollment request cancelled successfully.",
    "withdrawn" => "Successfully withdrawn from course.",
    "course_list_retrieved" => "Course enrollment list retrieved successfully.",
    "list_retrieved" => "Enrollment list retrieved successfully.",
    "status_retrieved" => "Enrollment status retrieved successfully.",
    "course_not_managed" => "Course not found or not under your management.",
    "no_view_all_access" => "You do not have access to view all enrollments.",
    "no_view_course_access" => "You do not have access to view enrollments for this course.",
    "no_view_access" => "You do not have access to view this enrollment.",
    "no_view_status_access" => "You do not have access to view this enrollment status.",
    "no_cancel_access" => "You do not have access to cancel this enrollment.",
    "no_withdraw_access" => "You do not have access to withdraw from this enrollment.",
    "no_approve_access" => "You do not have access to approve this enrollment.",
    "no_reject_access" => "You do not have access to reject this enrollment.",
    "no_remove_access" => "You do not have access to remove participant from this course.",
    "student_only" => "Only students can enroll in courses.",
    "request_not_found" => "Enrollment request not found for this course.",
    "expelled" => "Participant successfully removed from course.",
    "not_enrolled" => "You are not enrolled in this course.",
    "approved" => "Enrollment request approved.",
    "rejected" => "Enrollment request rejected.",
    "key_required" => "Enrollment key is required.",
    "key_invalid" => "Enrollment key is invalid.",
  ],

  // Assignments Module
  "assignments" => [
    "submitted" => "Assignment submitted successfully.",
    "not_found" => "Assignment not found.",
  ],

  // Submissions Module
  "submissions" => [
    "created" => "Submission created successfully.",
    "not_found" => "Submission not found.",
  ],

  // Learning Module
  "learning" => [
    "progress_saved" => "Learning progress saved successfully.",
  ],

  // Challenges Module
  "challenges" => [
    "created" => "Challenge created successfully.",
    "completed" => "Challenge completed successfully.",
    "not_found" => "Challenge not found.",
    "retrieved" => "Challenge retrieved successfully.",
    "completions_retrieved" => "Challenge completions retrieved successfully.",
    "reward_claimed" => "Reward successfully claimed!",
  ],

  // Gamification Module
  "gamification" => [
    "points_awarded" => "Points awarded successfully.",
  ],

  // Forums Module
  "forums" => [
    "reaction_added" => "Reaction added successfully.",
    "statistics_retrieved" => "Forum statistics retrieved successfully.",
  ],

  // Notifications Module
  "notifications" => [
    "list_retrieved" => "Notifications retrieved successfully.",
    "marked_read" => "Notification marked as read.",
    "preferences_updated" => "Notification preferences updated successfully",
    "preferences_reset" => "Notification preferences reset to defaults successfully",
    "failed_update_preferences" => "Failed to update notification preferences.",
    "failed_reset_preferences" => "Failed to reset notification preferences.",
  ],

  // Search Module
  "search" => [
    "history_cleared" => "Search history cleared successfully",
    "history_deleted" => "Search history entry deleted successfully",
  ],

  // Permission messages
  "permission_denied" => "Permission denied.",
  "insufficient_permissions" => "You do not have sufficient permissions.",
  "role_required" => "This action requires :role role.",

  // Validation messages
  "invalid_input" => "Invalid input provided.",
  "missing_required_field" => "Required field is missing.",
];
