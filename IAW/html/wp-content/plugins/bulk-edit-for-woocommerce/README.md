# pbe


Select and order by


SELECT ID, post_parent
FROM `wp_posts`
WHERE post_type IN  ('product', 'product_variation' )

ORDER BY
  CASE WHEN post_parent = 0 THEN id ELSE post_parent END, 
  post_parent,
  id
LIMIT 50



```sql

SELECT ID, post_parent, post_title, post_type
FROM `wp_posts`
WHERE 
  post_type IN  ('product', 'product_variation' )
  AND (
	  (
		post_type = 'product'
		AND (
			post_title not LIKE '%-%'
		)
	  )
	  OR (  
		post_type = 'product_variation'
  	 	AND (
		   post_title like LIKE '%Blue%'
	   )
	  )
 )
	
ORDER BY
  CASE WHEN post_parent = 0 THEN id ELSE post_parent END, 
  post_parent,
  id
LIMIT 50

```

NOt SET Meta `WHERE mt1.post_id IS NULL`