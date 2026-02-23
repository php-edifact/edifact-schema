<?php
require('vendor/autoload.php');

// Get edition from command line argument or default to D95B
$edition = $argv[1] ?? 'D95B';
$basePath = "https://php-edifact.github.io/edifact-schema/";

// List of valid EDIFACT versions
$validEditions = [
    'D00A', 'D00B', 'D01A', 'D01B', 'D01C', 'D02A', 'D02B', 'D03A', 'D03B',
    'D04A', 'D04B', 'D05A', 'D05B', 'D06A', 'D06B', 'D07A', 'D07B', 'D08A',
    'D08B', 'D09A', 'D09B', 'D10A', 'D10B', 'D11A', 'D11B', 'D12A', 'D12B',
    'D13A', 'D13B', 'D14A', 'D14B', 'D15A', 'D15B', 'D16A', 'D16B', 'D17A',
    'D17B', 'D18A', 'D18B', 'D19A', 'D19B', 'D20A', 'D20B', 'D21A', 'D21B',
    'D22A', 'D22B', 'D23A', 'D24A', 'D94B', 'D95A', 'D95B', 'D96A', 'D96B',
    'D97A', 'D97B', 'D98A', 'D98B', 'D99A', 'D99B'
];

// If 'all' is specified, generate all versions
$editionsToGenerate = ($edition === 'all') ? $validEditions : [$edition];

echo "Generating EDIFACT JSON Schemas...\n";
echo "Target edition(s): " . implode(', ', $editionsToGenerate) . "\n\n";

foreach ($editionsToGenerate as $edition) {
    echo "Processing edition: $edition\n";
    generateEdition($edition, $basePath);
}

echo "\nDone!\n";

function generateEdition($edition, $basePath) {
    $ARCHdataelement = [];
    $ARCHcompositedataelement = [];
    $ARCHsegment = [];
    $ARCHmessage = [];

    $mapping = new \EDI\Mapping\MappingProvider($edition);
    $codes = $mapping->loadCodesXml();

    if (!file_exists('./'.$edition)) {
        mkdir('./'.$edition, 0777, true);
        mkdir('./'.$edition.'/dataelement', 0777, true);
        mkdir('./'.$edition.'/compositedataelement', 0777, true);
        mkdir('./'.$edition.'/segment', 0777, true);
        mkdir('./'.$edition.'/message', 0777, true);
    }

    $segs = $mapping->getSegments();
    $msgs = $mapping->listMessages();
    $xml = simplexml_load_file($segs);

    foreach ($xml as $segment) {
        create_segment($edition, $segment);
    }

    foreach ($msgs as $msg) {
        $msgXml = simplexml_load_file($mapping->getMessage($msg));
        create_message($edition, $msgXml, $msg);
    }

    echo "  - Generated " . count($msgs) . " messages\n";
}

function create_segment($edition, $segment) {
    global $basePath, $ARCHsegment;
    $segmentSchema = [
        '$schema' => "https://json-schema.org/draft/2020-12/schema",
        '$id' => $basePath.$edition."/segment/ADR.edifact.schema.json",
        'title' => "ADR",
        'description' => "",
        'type' => "object",
        'properties' => [],
        'required' => []
    ];

    $segAtt = $segment->attributes();
    $segName = ''.$segAtt['id'];
    $segmentSchema['$id'] = $basePath.$edition."/segment/".$segName.".edifact.schema.json";
    $segmentSchema['title'] = $segName;
    $segmentSchema['description'] = ''.$segAtt['desc'];

    $props = [];
    foreach($segment->children() as $child) {

        if ($child->getName() == 'data_element') {
            $cldAtt = $child->attributes();
            $name = ''.$cldAtt['name'];

            $tmpPr = [
                '$ref' => $basePath.$edition."/dataelement/".$cldAtt['id'].".edifact.schema.json"
            ];


            if (!isset($props[$name])) {
                $props[$name] = 0;
            }
            if (isset($props[$name])) {
                $props[$name]++;

                if ($props[$name] > 1)
                    $name = $name.$props[$name]; //if there's many times?
            }

            $segmentSchema['properties'][$name] = $tmpPr;

            create_dataelement($edition, $child);
        }
        if ($child->getName() == 'composite_data_element') {
            $cldAtt = $child->attributes();
            $name = ''.$cldAtt['name'];
            $tmpPr = [
                '$ref' => $basePath.$edition."/compositedataelement/".$cldAtt['id'].".edifact.schema.json"
            ];
            if (!isset($props[$name])) {
                $props[$name] = 0;
            }
            if (isset($props[$name])) {
                $props[$name]++;

                if ($props[$name] > 1)
                    $name = $name.$props[$name]; //if there's many times?
            }
            $segmentSchema['properties'][$name] = $tmpPr;
            create_compositedataelement($edition, $child);
        }
    }

    $ARCHsegment[$segName] = $segmentSchema['properties'];
    file_put_contents(
        './'.$edition.'/segment/'.$segName.'.edifact.schema.json',
        json_encode($segmentSchema, JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT)
    );
}

function create_compositedataelement($edition, $element) {
    global $basePath;
    $segmentSchema = [
        '$schema' => "https://json-schema.org/draft/2020-12/schema",
        '$id' => $basePath.$edition."/compositedataelement/C000.edifact.schema.json",
        'title' => "C000",
        'description' => "",
        'type' => "object",
        'properties' => [],
        'required' => []
    ];

    $segAtt = $element->attributes();
    $segName = ''.$segAtt['id'];
    $segmentSchema['$id'] = $basePath.$edition."/compositedataelement/".$segName.".edifact.schema.json";
    $segmentSchema['title'] = $segName;
    $segmentSchema['description'] = ''.$segAtt['desc'];

    $props = [];
    foreach($element->children() as $child) {
        if ($child->getName() == 'data_element') {
            $cldAtt = $child->attributes();
            $name = ''.$cldAtt['name'];
            $tmpPr = [
                '$ref' => $basePath.$edition."/dataelement/".$cldAtt['id'].".edifact.schema.json"
            ];
            if (!isset($props[$name])) {
                $props[$name] = 0;
            }
            if (isset($props[$name])) {
                $props[$name]++;
                if ($props[$name] > 1)
                    $name = $name.$props[$name]; //if there's many times?
            }
            $segmentSchema['properties'][$name] = $tmpPr;

            create_dataelement($edition, $child);
        }
    }

    file_put_contents(
        './'.$edition.'/compositedataelement/'.$segName.'.edifact.schema.json',
        json_encode($segmentSchema, JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT)
    );
}

function create_dataelement($edition, $element) {
    global $basePath, $codes;
    $segmentSchema = [
        '$schema' => "https://json-schema.org/draft/2020-12/schema",
        '$id' => $basePath.$edition."/dataelement/000.edifact.schema.json",
        'title' => "000",
        'description' => "",
        'type' => "object",
        'properties' => [],
        'required' => []
    ];

    $segAtt = $element->attributes();
    $segName = ''.$segAtt['id'];
    $segmentSchema['$id'] = $basePath.$edition."/dataelement/".$segName.".edifact.schema.json";
    $segmentSchema['title'] = ''.$segAtt['name'];
    $segmentSchema['description'] = ''.$segAtt['desc'];

    $type = 'string';

    $segmentSchema['properties'] = [
        "value" => [
            "description" => "Value",
            "type" => $type,
            "maxLength" => (int)$segAtt['maxlength']
        ]
    ];

    if (isset($codes[$segName])) {
        $values = $codes[$segName];
        $oneOf = [];
        foreach ($values as $k => $v) {
            $oneOf[] = [
                'const' => (string)$k,
                'description' => $v
            ];
        }
        $segmentSchema['properties']['value']['oneOf'] = $oneOf;
    }

    file_put_contents(
        './'.$edition.'/dataelement/'.$segName.'.edifact.schema.json',
        json_encode($segmentSchema, JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT)
    );
}

function create_message_recurse(&$segmentSchema, &$props, $child) {
    global $ARCHsegment;
    if ($child->getName() == 'defaults') {
    }

    if ($child->getName() == 'segment') {
        $cldAtt = $child->attributes();
        $name = ''.$cldAtt['id'];
        $segCode = $name;
        $isRequired = isset($cldAtt['required']) && ''.$cldAtt['required'] === 'true';

        if (!isset($props[$name])) {
            $props[$name] = 0;
        }
        if (isset($props[$name])) {
            $props[$name]++;
            if ($props[$name] > 1)
                $name = $name.$props[$name];
        }

        $segmentSchema['properties'][$name] = [
            "description" => "Segment ".$segCode,
            "type" => 'object',
            "properties" => $ARCHsegment[$segCode] ?? []
        ];

        if ($isRequired) {
            $segmentSchema['required'][] = $name;
        }
    }
    if ($child->getName() == 'group') {
        $cldAtt = $child->attributes();
        $name = ''.$cldAtt['id'];
        $segCode = $name;
        $isRequired = isset($cldAtt['required']) && ''.$cldAtt['required'] === 'true';

        if (!isset($props[$name])) {
            $props[$name] = 0;
        }
        if (isset($props[$name])) {
            $props[$name]++;
            if ($props[$name] > 1) {
                $name = $name.$props[$name];
            }
        }

        $segmentSchema['properties'][$name] = [
            "description" => "Group ".$segCode,
            "type" => 'object',
            "properties" => []
        ];

        $segmentSchema['properties'][$name]['required'] = [];

        if ($isRequired) {
            $segmentSchema['required'][] = $name;
        }

        foreach($child->children() as $child2) {
            if ($child2->getName() == 'segment') {
                $cldAtt2 = $child2->attributes();
                $name2 = ''.$cldAtt2['id'];
                $segCode2 = $name2;
                $isRequired2 = isset($cldAtt2['required']) && ''.$cldAtt2['required'] === 'true';

                if (!isset($props[$name2])) {
                    $props[$name2] = 0;
                }
                if (isset($props[$name2])) {
                    $props[$name2]++;
                    if ($props[$name] > 1)
                        $name2 = $name2.$props[$name2];
                }

                $segmentSchema['properties'][$name]['properties'][$name2] = [
                    "description" => "Segment ".$segCode2,
                    "type" => 'object',
                    "properties" => $ARCHsegment[$segCode2]
                ];

                if ($isRequired2) {
                    $segmentSchema['properties'][$name]['required'][] = $name2;
                }
            }
            if ($child2->getName() == 'group') {
                $cldAtt2 = $child2->attributes();
                $name2 = ''.$cldAtt2['id'];
                $segCode2 = $name2;
                $isRequired2 = isset($cldAtt2['required']) && ''.$cldAtt2['required'] === 'true';

                if (!isset($props[$name2])) {
                    $props[$name2] = 0;
                }
                if (isset($props[$name2])) {
                    $props[$name2]++;
                    if ($props[$name2] > 1) {
                        $name2 = $name2.$props[$name2];
                    }
                }

                $segmentSchema['properties'][$name]['properties'][$name2] = [
                    "description" => "Group ".$segCode2,
                    "type" => 'object',
                    "properties" => []
                ];
                $segmentSchema['properties'][$name]['properties'][$name2]['required'] = [];

                if ($isRequired2) {
                    $segmentSchema['properties'][$name]['required'][] = $name2;
                }

                foreach($child2->children() as $child3) {
                    if ($child3->getName() == 'segment') {
                        $cldAtt3 = $child3->attributes();
                        $name3 = ''.$cldAtt3['id'];
                        $segCode3 = $name3;
                        $isRequired3 = isset($cldAtt3['required']) && ''.$cldAtt3['required'] === 'true';

                        if (!isset($props[$name3])) {
                            $props[$name3] = 0;
                        }
                        if (isset($props[$name3])) {
                            $props[$name3]++;
                            if ($props[$name] > 1)
                                $name3 = $name3.$props[$name3];
                        }

                        $segmentSchema['properties'][$name]['properties'][$name2]['properties'][$name3] = [
                            "description" => "Segment ".$segCode3,
                            "type" => 'object',
                            "properties" => $ARCHsegment[$segCode3]
                        ];

                        if ($isRequired3) {
                            $segmentSchema['properties'][$name]['properties'][$name2]['required'][] = $name3;
                        }
                    }
                    if ($child3->getName() == 'group') {
                        $cldAtt3 = $child3->attributes();
                        $name3 = ''.$cldAtt3['id'];
                        $segCode3 = $name3;
                        $isRequired3 = isset($cldAtt3['required']) && ''.$cldAtt3['required'] === 'true';

                        if (!isset($props[$name3])) {
                            $props[$name3] = 0;
                        }
                        if (isset($props[$name3])) {
                            $props[$name3]++;
                            if ($props[$name3] > 1) {
                                $name3 = $name3.$props[$name3];
                            }
                        }

                        $segmentSchema['properties'][$name]['properties'][$name2]['properties'][$name3] = [
                            "description" => "Group ".$segCode3,
                            "type" => 'object',
                            "properties" => []
                        ];
                        $segmentSchema['properties'][$name]['properties'][$name2]['properties'][$name3]['required'] = [];

                        if ($isRequired3) {
                            $segmentSchema['properties'][$name]['properties'][$name2]['required'][] = $name3;
                        }
                    }
                }
            }
        }
    }
}

function create_message($edition, $message, $msgName) {
    global $ARCHsegment;
    global $basePath;
    $segmentSchema = [
        '$schema' => "https://json-schema.org/draft/2020-12/schema",
        '$id' => $basePath.$edition."/message/APERAK.edifact.schema.json",
        'title' => "ADR",
        'description' => "",
        'type' => "object",
        'properties' => [],
        'required' => []
    ];

    $segAtt = $message->attributes();
    $segName = strtoupper($msgName);
    $segmentSchema['$id'] = $basePath.$edition."/message/".$segName.".edifact.schema.json";
    $segmentSchema['title'] = $segName;
    $segmentSchema['description'] = ''.$segAtt['desc'];

    $props = [];
    foreach($message->children() as $child) {
        create_message_recurse($segmentSchema, $props, $child);
    }

    file_put_contents(
        './'.$edition.'/message/'.$segName.'.edifact.schema.json',
        json_encode($segmentSchema, JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT)
    );
}
