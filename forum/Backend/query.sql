SELECT *
FROM (wp_f_posts p INNER JOIN wp_users u ON p.user_id = u.ID)
      LEFT OUTER JOIN wp_f_posts p2 ON p.response_to = p2.post_id
WHERE p.topic_id = 1;