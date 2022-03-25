<?php


// Applies the data append attribute to all targets
function apply_single_data_attribute($node, $attribute) {
    $att_value = $node->getAttribute($attribute);
    if ($att_value) {
        $node->setAttribute("data-{$attribute}", $att_value);
        $node->removeAttribute($attribute);
    }
}

// Given a prop that is a variable, evaluate it
function evaluate_prop_var($value) {
    $value = rtrim(ltrim($value, '{'), '}');
    if ($value === 'true') return true;
    if ($value === 'false') return false;
    if (is_numeric($value)) return (float)$value;
    if (str_starts_with($value, '$')) {
        $var_name = str_replace('$', '', $value);
        global $$var_name;
        return $$var_name;
    }
    return $value;
}

// Check if the node is a component.
// If it is then transform it
function find_component($doc, $node) {
    $tag_name = $node->tagName;
    if (function_exists($tag_name)) {
        // Get the props passed to the node
        $props = [];
        if ($node->hasAttributes()) {
            foreach ($node->attributes as $attribute) {
                $id = $attribute->name;
                $value = $attribute->value;
                if (str_starts_with($value, '{') && str_ends_with($value, '}')) {
                    $value = evaluate_prop_var($value);
                }
                $props[$id] = $value;
            }
        }
        $inner_html = inner_html($node);
        var_dump($inner_html, $tag_name);
        if ($inner_html) {
            $props['children'] = $inner_html;
        }
        
        // The function exists, now we can transform it
        $fragment = $doc->createDocumentFragment();
        $fragment->appendXML($tag_name($props));
        $node->parentNode->replaceChild($fragment, $node);
    }
}

function apply_data_attributes($html) {
    $doc = new DOMDocument();
    libxml_use_internal_errors(true);
    $doc->loadHTML($html);
    libxml_clear_errors();

    $xpath = new DOMXPath($doc);
    // Get every element in the document
    $nodes = $xpath->query("//*");

    // First, make any transformations
    $nodes = iterator_to_array($nodes);
    foreach (array_reverse($nodes) as $node) {
        find_component($doc, $node);
    }
    // Need to make this more efficient
    $nodes = $xpath->query("//*");
    // Apply data attributes to the nodes that need them
    foreach ($nodes as $node) {
        apply_single_data_attribute($node, 'fn');
        apply_single_data_attribute($node, 'val');
    }
    return utf8_decode($doc->saveHTML($doc->documentElement));
}

function inner_html(\DOMElement $element) {
    $doc = $element->ownerDocument;

    $html = '';

    foreach ($element->childNodes as $node) {
        $html .= utf8_decode($doc->saveHTML($node));
    }

    return $html;
}