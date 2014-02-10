UPDATE `[#DB_PREFIX#]inbox_dialog` SET sender_unread = 0 WHERE sender_count = 0;
UPDATE `[#DB_PREFIX#]inbox_dialog` SET recipient_unread = 0 WHERE recipient_count = 0;