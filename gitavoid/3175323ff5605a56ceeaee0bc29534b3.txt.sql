SELECT  a.ID FROM article a JOIN USERS u ON a.AUTHOR_ID=u.ID  WHERE  a.ACTIVE=1 AND u.ACTIVE    