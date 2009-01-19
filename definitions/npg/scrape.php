<?php 

$root = 'http://www.nature.com/nrd/focus/apoptosis/index.html';
$base = __DIR__;

require $base . '/../../scrape.inc.php';

function process_dc_identifier($text){
  return preg_replace('/^:/', '', $text);
}

function process_atom_title($text){
  return clean_text($text);
}

function process_dc_creator($text){
  $text = clean_text($text);
  $authors = preg_split('/(\s*,\s+|\s+&\s+)/', $text);
  return $authors; 
}

function clean_text($text){
  $text = str_replace("\n", '', $text);
  $text = preg_replace('/\s+/', ' ', $text);
  $text = trim($text);
  return $text;
}

function should_continue($page, $dom){
  if ($page === 1)
    return true;
}