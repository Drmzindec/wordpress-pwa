<?php

class PtPwaRouteMapper implements RouteMapper {
    /**
     * Translate permalink structure from WP structure to PWA structure
     *
     * @param $permalink_structure
     * 
     * @return $article_pattern
     */
    public static function translatePermalinkStructure($permalink_structure)
    {
        $article_pattern = '/';

        if (!$permalink_structure) {
            $article_pattern =  '/?p=:id';
        } else {
            $param_keys = explode('/', $permalink_structure);
            
            foreach( $param_keys as $param ) {
                switch($param) {
                    case '%category%':
                        $article_pattern = $article_pattern . ':sectionSlug/';
                        break;
                    case '%postname%':
                        $article_pattern = $article_pattern . ':title/';
                        break;
                    case '%post_id%':
                        $article_pattern = $article_pattern . ':id/';
                        break;
                    case '%author%':
                        $article_pattern = $article_pattern . ':author/';
                        break;
                    case '%year%':
                        $article_pattern = $article_pattern . ':year/';
                        break;
                    case '%monthnum%':
                        $article_pattern = $article_pattern . ':month/';
                        break;
                    case '%day%':
                        $article_pattern = $article_pattern . ':day/';
                        break;
                    default: 
                        break;
                }
            }
        }

        return $article_pattern;
    }

    /**
     * Identifies if permalink has a trailing slash at the end
     *
     * @param $permalink_structure
     * 
     * @return $includeTrailingSlashes
     */
    public static function includeTrailingSlashes($permalink_structure)
    {
        return substr($permalink_structure, -1)  === "/";
    }

    /**
     * Creates an array of routes mapped to PWA structure
     *
     * @param $category_prefix
     * @param $article_pattern
     * @param $includeTrailingSlashes
     * 
     * @return $routes
     */
    public static function mapRoutes($category_prefix, $article_pattern, $includeTrailingSlashes)
    {
        $trailingSlash = $includeTrailingSlashes ? '/' : '';

        if (!empty($category_prefix)) {
            $category_prefix = "/" . $category_prefix;
        }

        return array(
            array(
                "name"      =>  "home",
                "pattern"   =>  "/",
                "page"      =>  "index" 
            ),
            array(
                "name"      =>  "list",
                "pattern"   =>  $category_prefix . "/:sectionSlug" . $trailingSlash,
                "page"      =>  "index" 
            ),
            array(
                "name"      =>  "list 2",
                "pattern"   =>  $category_prefix . "/:sectionSlug/:secondSectionSlug" . $trailingSlash,
                "page"      =>  "index" 
            ),
            array(
                "name"      =>  "article",
                "pattern"   =>  "/:articleSlug" . $trailingSlash,
                "page"      =>  "article" 
            ),
            array(
                "name"      =>  null,
                "pattern"   =>  $article_pattern,
                "page"      =>  "article" 
            ),
        );
    }
}

?>