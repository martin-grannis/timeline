<?php

function mb_extra_query_vars( $query_vars ){
    $query_vars[] = 'searchText';
    $query_vars[] = 'searchResourceCategory';
    $query_vars[] = 'searchVideoContrib';
    return $query_vars;
}
add_filter( 'query_vars', 'mb_extra_query_vars' );


