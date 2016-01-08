<?php
class ModelBlogArticle extends Model {
	public function updateViewed($article_id) {
		$this->db->query("UPDATE " . DB_PREFIX . "article SET viewed = (viewed + 1) WHERE article_id = '" . (int)$article_id . "'");
	}
	
	public function getArticle($article_id) {
		if ($this->customer->isLogged()) {
			$customer_group_id = $this->customer->getCustomerGroupId();
		} else {
			$customer_group_id = $this->config->get('config_customer_group_id');
		}	
				
		$query = $this->db->query("SELECT DISTINCT *, pd.name AS name, p.image, (SELECT AVG(rating) AS total FROM " . DB_PREFIX . "review_article r1 WHERE r1.article_id = p.article_id AND r1.status = '1' GROUP BY r1.article_id) AS rating, (SELECT COUNT(*) AS total FROM " . DB_PREFIX . "review_article r2 WHERE r2.article_id = p.article_id AND r2.status = '1' GROUP BY r2.article_id) AS reviews, p.sort_order FROM " . DB_PREFIX . "article p LEFT JOIN " . DB_PREFIX . "article_description pd ON (p.article_id = pd.article_id) LEFT JOIN " . DB_PREFIX . "article_to_store p2s ON (p.article_id = p2s.article_id)  WHERE p.article_id = '" . (int)$article_id . "' AND pd.language_id = '" . (int)$this->config->get('config_language_id') . "' AND p.status = '1' AND p.date_available <= NOW() AND p2s.store_id = '" . (int)$this->config->get('config_store_id') . "'");
		
		if ($query->num_rows) {
			return array(
				'seo_title'        => $query->row['seo_title'],
				'seo_h1'           => $query->row['seo_h1'],
				'article_id'       => $query->row['article_id'],
				'name'             => $query->row['name'],
				'description'      => $query->row['description'],
				'meta_description' => $query->row['meta_description'],
				'meta_keyword'     => $query->row['meta_keyword'],
				'image'            => $query->row['image'],
				'rating'           => round($query->row['rating']),
				'reviews'          => $query->row['reviews'],
				'sort_order'       => $query->row['sort_order'],
				'article_review'   => $query->row['article_review'],
				'status'           => $query->row['status'],
				'gstatus'           => $query->row['gstatus'],
				'date_added'       => $query->row['date_added'],
				'date_modified'    => $query->row['date_modified'],
				'viewed'           => $query->row['viewed']
			);
		} else {
			return false;
		}
	}

	public function getArticles($data = array()) {
		if ($this->customer->isLogged()) {
			$customer_group_id = $this->customer->getCustomerGroupId();
		} else {
			$customer_group_id = $this->config->get('config_customer_group_id');
		}	
		
		$cache = md5(http_build_query($data));
		
		$article_data = $this->cache->get('article.' . (int)$this->config->get('config_language_id') . '.' . (int)$this->config->get('config_store_id') . '.' . (int)$customer_group_id . '.' . $cache);
		
		if (!$article_data) {
			$sql = "SELECT p.article_id, (SELECT AVG(rating) AS total FROM " . DB_PREFIX . "review_article r1 WHERE r1.article_id = p.article_id AND r1.status = '1' GROUP BY r1.article_id) AS rating FROM " . DB_PREFIX . "article p LEFT JOIN " . DB_PREFIX . "article_description pd ON (p.article_id = pd.article_id) LEFT JOIN " . DB_PREFIX . "article_to_store p2s ON (p.article_id = p2s.article_id)"; 
						
			if (!empty($data['filter_news_id'])) {
				$sql .= " LEFT JOIN " . DB_PREFIX . "article_to_news p2c ON (p.article_id = p2c.article_id)";			
			}
			
			$sql .= " WHERE pd.language_id = '" . (int)$this->config->get('config_language_id') . "' AND p.status = '1' AND p.date_available <= NOW() AND p2s.store_id = '" . (int)$this->config->get('config_store_id') . "'"; 
			
			if (!empty($data['filter_name']) || !empty($data['filter_tag'])) {
				$sql .= " AND (";
				
				if (!empty($data['filter_name'])) {					
					if (!empty($data['filter_description'])) {
						$sql .= "LCASE(pd.name) LIKE '%" . $this->db->escape(utf8_strtolower($data['filter_name'])) . "%' OR MATCH(pd.description) AGAINST('" . $this->db->escape(utf8_strtolower($data['filter_name'])) . "')";
					} else {
						$sql .= "LCASE(pd.name) LIKE '%" . $this->db->escape(utf8_strtolower($data['filter_name'])) . "%'";
					}
				}
				
				if (!empty($data['filter_name']) && !empty($data['filter_tag'])) {
					$sql .= " OR ";
				}
				
				if (!empty($data['filter_tag'])) {
					$sql .= "MATCH(pd.tag) AGAINST('" . $this->db->escape(utf8_strtolower($data['filter_tag'])) . "')";
				}
			
				$sql .= ")";
				
				if (!empty($data['filter_name'])) {
					$sql .= " OR LCASE(p.model) = '" . $this->db->escape(utf8_strtolower($data['filter_name'])) . "'";
				}
				
				if (!empty($data['filter_name'])) {
					$sql .= " OR LCASE(p.sku) = '" . $this->db->escape(utf8_strtolower($data['filter_name'])) . "'";
				}	
				
				if (!empty($data['filter_name'])) {
					$sql .= " OR LCASE(p.upc) = '" . $this->db->escape(utf8_strtolower($data['filter_name'])) . "'";
				}		

				if (!empty($data['filter_name'])) {
					$sql .= " OR LCASE(p.ean) = '" . $this->db->escape(utf8_strtolower($data['filter_name'])) . "'";
				}

				if (!empty($data['filter_name'])) {
					$sql .= " OR LCASE(p.jan) = '" . $this->db->escape(utf8_strtolower($data['filter_name'])) . "'";
				}
				
				if (!empty($data['filter_name'])) {
					$sql .= " OR LCASE(p.isbn) = '" . $this->db->escape(utf8_strtolower($data['filter_name'])) . "'";
				}		
				
				if (!empty($data['filter_name'])) {
					$sql .= " OR LCASE(p.mpn) = '" . $this->db->escape(utf8_strtolower($data['filter_name'])) . "'";
				}					
			}
			
			if (!empty($data['filter_news_id'])) {
				if (!empty($data['filter_sub_news'])) {
					$implode_data = array();
					
					$implode_data[] = (int)$data['filter_news_id'];
					
					$this->load->model('blog/news');
					
					$categories = $this->model_blog_news->getCategoriesByParentId($data['filter_news_id']);
										
					foreach ($categories as $news_id) {
						$implode_data[] = (int)$news_id;
					}
								
					$sql .= " AND p2c.news_id IN (" . implode(', ', $implode_data) . ")";			
				} else {
					$sql .= " AND p2c.news_id = '" . (int)$data['filter_news_id'] . "'";
				}
			}		
					
			$sql .= " GROUP BY p.article_id";
			
			$sort_data = array(
				'pd.name',
				//ocshop
				'p.viewed',
				//ocshop
				'rating',
				'p.sort_order',
				'p.date_added'
			);	
			
			if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
				if ($data['sort'] == 'pd.name' || $data['sort'] == 'p.model' || $data['sort'] == 'p.date_added') {
					$sql .= " ORDER BY LCASE(" . $data['sort'] . ")";
				} else {
					$sql .= " ORDER BY " . $data['sort'];
				}
			} else {
				$sql .= " ORDER BY p.sort_order";	
			}
			
			if (isset($data['order']) && ($data['order'] == 'DESC')) {
				$sql .= " DESC, LCASE(pd.name) DESC";
			} else {
				$sql .= " ASC, LCASE(pd.name) ASC";
			}
		
			if (isset($data['start']) || isset($data['limit'])) {
				if ($data['start'] < 0) {
					$data['start'] = 0;
				}				
	
				if ($data['limit'] < 1) {
					$data['limit'] = 20;
				}	
			
				$sql .= " LIMIT " . (int)$data['start'] . "," . (int)$data['limit'];
			}
			
			$article_data = array();
					
			$query = $this->db->query($sql);
		
			foreach ($query->rows as $result) {
				$article_data[$result['article_id']] = $this->getArticle($result['article_id']);
			}
			
			//$this->cache->set('article.' . (int)$this->config->get('config_language_id') . '.' . (int)$this->config->get('config_store_id') . '.' . (int)$customer_group_id . '.' . $cache, $article_data);
		}
		
		return $article_data;
	}
		
	public function getLatestArticles($limit) {
		if ($this->customer->isLogged()) {
			$customer_group_id = $this->customer->getCustomerGroupId();
		} else {
			$customer_group_id = $this->config->get('config_customer_group_id');
		}	
				
		$article_data = $this->cache->get('article.latest.' . (int)$this->config->get('config_language_id') . '.' . (int)$this->config->get('config_store_id') . '.' . $customer_group_id . '.' . (int)$limit);

		if (!$article_data) { 
			$query = $this->db->query("SELECT p.article_id FROM " . DB_PREFIX . "article p LEFT JOIN " . DB_PREFIX . "article_to_store p2s ON (p.article_id = p2s.article_id) WHERE p.status = '1' AND p.date_available <= NOW() AND p2s.store_id = '" . (int)$this->config->get('config_store_id') . "' ORDER BY p.date_added DESC LIMIT " . (int)$limit);
		 	 
			foreach ($query->rows as $result) {
				$article_data[$result['article_id']] = $this->getArticle($result['article_id']);
			}
			
			$this->cache->set('article.latest.' . (int)$this->config->get('config_language_id') . '.' . (int)$this->config->get('config_store_id'). '.' . $customer_group_id . '.' . (int)$limit, $article_data);
		}
		
		return $article_data;
	}
	
	public function getPopularArticles($limit) {
		$article_data = array();
		
		$query = $this->db->query("SELECT p.article_id FROM " . DB_PREFIX . "article p LEFT JOIN " . DB_PREFIX . "article_to_store p2s ON (p.article_id = p2s.article_id) WHERE p.status = '1' AND p.date_available <= NOW() AND p2s.store_id = '" . (int)$this->config->get('config_store_id') . "' ORDER BY p.viewed DESC, p.date_added DESC LIMIT " . (int)$limit);
		
		foreach ($query->rows as $result) { 		
			$article_data[$result['article_id']] = $this->getArticle($result['article_id']);
		}
					 	 		
		return $article_data;
	}
		
	public function getArticleImages($article_id) {
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "article_image WHERE article_id = '" . (int)$article_id . "' ORDER BY sort_order ASC");

		return $query->rows;
	}
	
	public function getArticleRelated($article_id) {
		$article_data = array();

		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "article_related pr LEFT JOIN " . DB_PREFIX . "article p ON (pr.related_id = p.article_id) LEFT JOIN " . DB_PREFIX . "article_to_store p2s ON (p.article_id = p2s.article_id) WHERE pr.article_id = '" . (int)$article_id . "' AND p.status = '1' AND p.date_available <= NOW() AND p2s.store_id = '" . (int)$this->config->get('config_store_id') . "'");
		
		foreach ($query->rows as $result) { 
			$article_data[$result['related_id']] = $this->getArticle($result['related_id']);
		}
		
		return $article_data;
	}
	
	//category manuf
	public function getArticleRelated_by_category($category_id, $limit) {

		$article_data = array();
				
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "article_related_wb pr LEFT JOIN " . DB_PREFIX . "article p ON (pr.article_id = p.article_id) LEFT JOIN " . DB_PREFIX . "article_to_store p2s ON (p.article_id = p2s.article_id) WHERE pr.category_id = '" . (int)$category_id . "' AND p.status = '1' AND p.date_available <= NOW() AND p2s.store_id = '" . (int)$this->config->get('config_store_id') . "' LIMIT " . (int)$limit); 

		foreach ($query->rows as $result) { 

			$article_data[$result['article_id']] = $this->getArticle($result['article_id']);

		}

		

		return $article_data;

	}
	
public function getArticleRelated_by_manufacturer($manufacturer_id, $limit) {

		$article_data = array();

		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "article_related_mn pr LEFT JOIN " . DB_PREFIX . "article p ON (pr.article_id = p.article_id) LEFT JOIN " . DB_PREFIX . "article_to_store p2s ON (p.article_id = p2s.article_id) WHERE pr.manufacturer_id = '" . (int)$manufacturer_id . "' AND p.status = '1' AND p.date_available <= NOW() AND p2s.store_id = '" . (int)$this->config->get('config_store_id') . "' LIMIT " . (int)$limit); 

		foreach ($query->rows as $result) { 

			$article_data[$result['article_id']] = $this->getArticle($result['article_id']);

		}

		

		return $article_data;

	}
	//category manuf
	
	public function getArticleRelatedProduct($article_id) {
		$product_data = array();
		$this->load->model('catalog/product');
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "article_related_product np LEFT JOIN " . DB_PREFIX . "product p ON (np.product_id = p.product_id) LEFT JOIN " . DB_PREFIX . "product_to_store p2s ON (p.product_id = p2s.product_id) WHERE np.article_id = '" . (int)$article_id . "' AND p.status = '1' AND p.date_available <= NOW() AND p2s.store_id = '" . (int)$this->config->get('config_store_id') . "'");
		
		foreach ($query->rows as $result) { 
			$product_data[$result['product_id']] = $this->model_catalog_product->getProduct($result['product_id']);
		}

		return $product_data;
	}
		
	public function getArticleLayoutId($article_id) {
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "article_to_layout WHERE article_id = '" . (int)$article_id . "' AND store_id = '" . (int)$this->config->get('config_store_id') . "'");
		
		if ($query->num_rows) {
			return $query->row['layout_id'];
		} else {
			return  $this->config->get('config_layout_article');
		}
	}
	
	public function getCategories($article_id) {
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "article_to_news WHERE article_id = '" . (int)$article_id . "'");
		
		return $query->rows;
	}

	public function getDownloads($article_id) {

		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "article_to_download pd LEFT JOIN " . DB_PREFIX . "download d ON(pd.download_id=d.download_id) LEFT JOIN " . DB_PREFIX . "download_description dd ON(pd.download_id=dd.download_id) WHERE article_id = '" . (int)$article_id . "' AND dd.language_id = '" . (int)$this->config->get('config_language_id')."'");

		return $query->rows;
	}

	public function getDownload($article_id, $download_id) {
	$download="";
	if($download_id!=0)$download=" AND d.download_id=".(int)$download_id;
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "article_to_download pd LEFT JOIN " . DB_PREFIX . "download d ON(pd.download_id=d.download_id) LEFT JOIN " . DB_PREFIX . "download_description dd ON(pd.download_id=dd.download_id) WHERE article_id = '" . (int)$article_id . "' ".$download." AND dd.language_id = '" . (int)$this->config->get('config_language_id')."'");

		return $query->row;
	}
		
	public function getTotalArticles($data = array()) {
		if ($this->customer->isLogged()) {
			$customer_group_id = $this->customer->getCustomerGroupId();
		} else {
			$customer_group_id = $this->config->get('config_customer_group_id');
		}	
				
		$cache = md5(http_build_query($data));
		
		$article_data = $this->cache->get('article.total.' . (int)$this->config->get('config_language_id') . '.' . (int)$this->config->get('config_store_id') . '.' . (int)$customer_group_id . '.' . $cache);
		
		if (!$article_data) {
			$sql = "SELECT COUNT(DISTINCT p.article_id) AS total FROM " . DB_PREFIX . "article p LEFT JOIN " . DB_PREFIX . "article_description pd ON (p.article_id = pd.article_id) LEFT JOIN " . DB_PREFIX . "article_to_store p2s ON (p.article_id = p2s.article_id)";
	
			if (!empty($data['filter_news_id'])) {
				$sql .= " LEFT JOIN " . DB_PREFIX . "article_to_news p2c ON (p.article_id = p2c.article_id)";			
			}
						
			$sql .= " WHERE pd.language_id = '" . (int)$this->config->get('config_language_id') . "' AND p.status = '1' AND p.date_available <= NOW() AND p2s.store_id = '" . (int)$this->config->get('config_store_id') . "'";
			
			if (!empty($data['filter_name']) || !empty($data['filter_tag'])) {
				$sql .= " AND (";
				
				if (!empty($data['filter_name'])) {					
					if (!empty($data['filter_description'])) {
						$sql .= "LCASE(pd.name) LIKE '%" . $this->db->escape(utf8_strtolower($data['filter_name'])) . "%' OR MATCH(pd.description) AGAINST('" . $this->db->escape(utf8_strtolower($data['filter_name'])) . "')";
					} else {
						$sql .= "LCASE(pd.name) LIKE '%" . $this->db->escape(utf8_strtolower($data['filter_name'])) . "%'";
					}
				}
				
				if (!empty($data['filter_name']) && !empty($data['filter_tag'])) {
					$sql .= " OR ";
				}
				
				if (!empty($data['filter_tag'])) {
					$sql .= "MATCH(pd.tag) AGAINST('" . $this->db->escape(utf8_strtolower($data['filter_tag'])) . "')";
				}
			
				$sql .= ")";
				
				if (!empty($data['filter_name'])) {
					$sql .= " OR LCASE(p.model) = '" . $this->db->escape(utf8_strtolower($data['filter_name'])) . "'";
				}
				
				if (!empty($data['filter_name'])) {
					$sql .= " OR LCASE(p.sku) = '" . $this->db->escape(utf8_strtolower($data['filter_name'])) . "'";
				}	
				
				if (!empty($data['filter_name'])) {
					$sql .= " OR LCASE(p.upc) = '" . $this->db->escape(utf8_strtolower($data['filter_name'])) . "'";
				}		

				if (!empty($data['filter_name'])) {
					$sql .= " OR LCASE(p.ean) = '" . $this->db->escape(utf8_strtolower($data['filter_name'])) . "'";
				}

				if (!empty($data['filter_name'])) {
					$sql .= " OR LCASE(p.jan) = '" . $this->db->escape(utf8_strtolower($data['filter_name'])) . "'";
				}
				
				if (!empty($data['filter_name'])) {
					$sql .= " OR LCASE(p.isbn) = '" . $this->db->escape(utf8_strtolower($data['filter_name'])) . "'";
				}		
				
				if (!empty($data['filter_name'])) {
					$sql .= " OR LCASE(p.mpn) = '" . $this->db->escape(utf8_strtolower($data['filter_name'])) . "'";
				}				
			}
						
			if (!empty($data['filter_news_id'])) {
				if (!empty($data['filter_sub_news'])) {
					$implode_data = array();
					
					$implode_data[] = (int)$data['filter_news_id'];
					
					$this->load->model('blog/news');
					
					$categories = $this->model_blog_news->getCategoriesByParentId($data['filter_news_id']);
										
					foreach ($categories as $news_id) {
						$implode_data[] = (int)$news_id;
					}
								
					$sql .= " AND p2c.news_id IN (" . implode(', ', $implode_data) . ")";			
				} else {
					$sql .= " AND p2c.news_id = '" . (int)$data['filter_news_id'] . "'";
				}
			}		
			
			$query = $this->db->query($sql);
			
			$article_data = $query->row['total']; 
			
			$this->cache->set('article.total.' . (int)$this->config->get('config_language_id') . '.' . (int)$this->config->get('config_store_id') . '.' . (int)$customer_group_id . '.' . $cache, $article_data);
		}
		
		return $article_data;
	}
		
}
?>