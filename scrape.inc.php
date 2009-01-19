<?php

set_include_path(get_include_path() . PATH_SEPARATOR . __DIR__);
require 'Zend/Dom/Query.php';

$definitions = json_decode(file_get_contents($definition_file));
debug($definitions);

$entries = array();

if (!isset($page))
  $page = 1;

do {
  $url = strstr($root, '%') ? sprintf($root, $page) : $root;
  $html = @DOMDocument::loadHTML(get($url));  
  $root_dom = new Zend_Dom_Query($html->saveHTML());
  
  $root_items = $root_dom->query($definitions->root);
  
  foreach ($root_items as $root_item){
    $dom = new Zend_Dom_Query($root_item->ownerDocument->saveXML($root_item));
    $output = new stdClass();
    
    foreach ($definitions->properties as $property => $def){
      list($selectors, $type) = $def;
      
      if (!is_array($selectors))
        $selectors = array($selectors);

      foreach ($selectors as $selector){
        if (preg_match('/(.+)?\battr\((.+?)\)$/', $selector, $matches))
          list($null, $selector, $attr) = $matches;
        else
          $attr = NULL;
          
        if ($selector)
          $result = select_data($dom, $selector);
        else
          $result = $root_item;
                      
        $output->{$property} = node_data($result, $type, $attr);
        break;
      }
    }
    
    array_walk($output, 'fix_data');
    $entries[] = $output;
  }
  
  $page++;
} while (should_continue($page, $root_dom));

debug($entries);

function select_data($dom, $selector){
  $results = array();
  $items = $dom->query($selector);
  foreach ($items as $item)
    $results[] = $item;
  return $results[0]; // TODO: more than one node matched by selector
}

function node_data($node, $type, $attr = NULL){
  $node = simplexml_import_dom($node);
  if ($attr)
    $node = $node[$attr];
  
  switch ($type){
    case 'html':
      $result = innerHTML(dom_import_simplexml($node));
    break;
    
    case 'text':
    default:
      $result = (string) $node;
    break;
  }
  
  return $result;
}

function fix_data(&$value, $key){
    $function = 'process_' . preg_replace('/\W/', '_', $key);
    if (function_exists($function))
      $value = call_user_func($function, $value);
}

function innerHTML($node){
  $doc = new DOMDocument();
  foreach ($node->childNodes as $child)
    $doc->appendChild($doc->importNode($child, true));
    
  return $doc->saveHTML();
}

function get($url){
  print $url . "\n";
  $file = sprintf(__DIR__ . '/cache/%s.html', md5($url));
  if (file_exists($file))
    return file_get_contents($file);
  
  $data = file_get_contents($url);
  if ($data)
    file_put_contents($file, $data);
  
  sleep(1);
  
  return $data;
}

function debug($t){
  $debug = 1;
  
  if ($debug){
    print_r($t);
    print "\n";
  }
}
