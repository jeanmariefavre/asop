<?php

function startsWith($haystack, $needle){ 
  return substr($haystack, 0, strlen($needle)) === $needle;
}

function endsWith($haystack, $needle)
{
  return (substr($haystack, - strlen($needle)) === $needle);
}

function prefixIfNeeded($str, $prefix) {
  return startsWith($str,$prefix) ? $str : $prefix . $str ;
}

function withoutOptionalPrefix($str, $prefix) {
  return startsWith($str,$prefix) ? substr($str,strlen($prefix)) : $str  ;
}