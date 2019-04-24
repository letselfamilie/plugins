SELECT dialog_id
FROM wp_c_dialogs
WHERE (user1_id = 2 AND user2_id = 3) OR (user1_id = 21 AND user2_id = 3);