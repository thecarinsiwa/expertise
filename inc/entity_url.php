<?php
/**
 * Construction d’URL pour les fiches (mission, announcement, project, offre, teams).
 * Utilise le paramètre ?id= (entier). Ne dépend pas de url_hash.
 *
 * Usage : require_once __DIR__ . '/inc/entity_url.php';
 *         $url = entity_url($baseUrl, 'mission', $id);
 *         $url = entity_url($baseUrl, 'announcement', $id, 'comment=1');
 */
if (!function_exists('entity_url')) {
    /**
     * @param string $baseUrl ex. '' ou '/expertise/'
     * @param string $page nom de la page : 'mission', 'announcement', 'project', 'offre', 'teams'
     * @param int $id
     * @param string $extraQuery query string à ajouter après ?id= (sans & initial), ex. 'comment=1'
     * @return string
     */
    function entity_url($baseUrl, $page, $id, $extraQuery = '') {
        $base = $baseUrl !== '' ? rtrim($baseUrl, '/') . '/' : '';
        $suffix = (substr($page, -4) !== '.php') ? '.php' : '';
        $url = $base . ($page . $suffix) . '?id=' . (int) $id;
        if ($extraQuery !== '') {
            $url .= '&' . ltrim($extraQuery, '&');
        }
        return $url;
    }
}
