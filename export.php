<?php

error_reporting(E_ALL & ~E_NOTICE | E_STRICT);

ini_set('xdebug.var_display_max_depth', -1);
ini_set('xdebug.var_display_max_children', -1);
ini_set('xdebug.var_display_max_data', -1);

include_once "config.php";
include_once "WSSoapClient.php";


function debugLog($client, $response)
{
    echo "====== REQUEST HEADERS =====" . PHP_EOL;
    var_dump($client->__getLastRequestHeaders());
    echo "========= REQUEST ==========" . PHP_EOL;
    var_dump($client->__getLastRequest());
    echo "========= RESPONSE =========" . PHP_EOL;
    var_dump($response);
}

function getProjects()
{
    global $swiftKanbanLogin;
    global $swiftKanbanPassword;

    $client = new WSSoapClient("https://login.swift-kanban.com/axis2/services/ProjectService?wsdl", array('trace' => 1));
    $client->__setUsernameToken($swiftKanbanLogin, $swiftKanbanPassword);
    $response = $client->__soapCall('getProjectsByOrganization',
        array('parameters' =>
                  array('orgAdminLoginId' => $swiftKanbanLogin),
        )
    );

    //debugLog($client, $response);

    return (String)$response->any;
}

function getCards($projectId)
{
    global $swiftKanbanLogin;
    global $swiftKanbanPassword;

    $client = new WSSoapClient("https://login.swift-kanban.com/axis2/services/KanbanCardService?wsdl", array('trace' => 1));
    $client->__setUsernameToken($swiftKanbanLogin, $swiftKanbanPassword);
    $response = $client->__soapCall('getCardsByFilter',
        array('parameters' =>
                  array(
                      'projectId'  => $projectId,
                      'cardType'   => 'UserStory',
                      'FilterType' => 'Example:compund',
                  ),
        )
    );

    //debugLog($client, $response);

    return (String)$response->any;
}


function getCard($projectId, $cardId)
{
    global $swiftKanbanLogin;
    global $swiftKanbanPassword;

    $client = new WSSoapClient("https://login.swift-kanban.com/axis2/services/KanbanCardService?wsdl", array('trace' => 1));
    $client->__setUsernameToken($swiftKanbanLogin, $swiftKanbanPassword);
    $response = $client->__soapCall('getCard',
        array('parameters' =>
                  array(
                      'projectId'    => $projectId,
                      'cardType'     => 'UserStory',
                      'cardUniqueId' => $cardId,
                  ),
        )
    );

    //debugLog($client, $response);

    return (String)$response->any;
}

function getAttachmentInfo($cardId)
{
    global $swiftKanbanLogin;
    global $swiftKanbanPassword;

    $client = new WSSoapClient("https://login.swift-kanban.com/axis2/services/ExtensionService?wsdl", array('trace' => 1));
    $client->__setUsernameToken($swiftKanbanLogin, $swiftKanbanPassword);
    $response = $client->__soapCall('getAttachmentsInfo',
        array('parameters' =>
                  array(
                      'cardUniqueId' => $cardId,
                      'cardType'     => 'UserStory',
                  ),
        )
    );

    //debugLog($client, $response);

    return (String)$response->any;
}

function getAttachment($attachmentId)
{

    global $swiftKanbanLogin;
    global $swiftKanbanPassword;

    $client = new WSSoapClient("https://login.swift-kanban.com/axis2/services/ExtensionService?wsdl", array('trace' => 1));
    $client->__setUsernameToken($swiftKanbanLogin, $swiftKanbanPassword);
    $response = $client->__soapCall('getAttachmentContent',
        array('parameters' =>
                  array(
                      'attachmentId' => $attachmentId,
                  ),
        )
    );

    //debugLog($client, $response);

    return (String)$response->any;
}

function getComments($cardId)
{
    global $swiftKanbanLogin;
    global $swiftKanbanPassword;

    $client = new WSSoapClient("https://login.swift-kanban.com/axis2/services/ExtensionService?wsdl", array('trace' => 1));
    $client->__setUsernameToken($swiftKanbanLogin, $swiftKanbanPassword);
    $response = $client->__soapCall('getComments',
        array('parameters' =>
                  array(
                      'cardUniqueId' => $cardId,
                      'cardType'     => 'UserStory',
                  ),
        )
    );

    //debugLog($client, $response);

    return (String)$response->any;
}

$sProjects = getProjects();
file_put_contents('xml_dump/projects.xml', $sProjects);

$p = xml_parser_create();
xml_parse_into_struct($p, $sProjects, $aProjects, $index);
xml_parser_free($p);

foreach ($aProjects as $aProject) {

    if ($aProject['attributes']) {
        $projectId   = $aProject['attributes']['NS:PROJECTID'];
        $projectName = $aProject['attributes']['NS:PROJECTNAME'];
        echo $projectName . "\n";

        $sCards = getCards($projectId);
        file_put_contents("xml_dump/project-$projectId-$projectName.xml", $sCards);

        $p = xml_parser_create();
        xml_parse_into_struct($p, $sCards, $aCards, $index);
        xml_parser_free($p);

        foreach ($aCards as $aCard) {
            if ($aCard['attributes']) {
                $cardId   = $aCard['attributes']['NS:CARDID'];
                $cardName = $aCard['attributes']['NS:CARDSEQUENCENUMBER'];
                echo $projectName . ' ' . $cardName . "\n";

                sleep(1);
                $sCardData = getCard($projectId, $cardId);
                file_put_contents("xml_dump/card-$projectId-$projectName-$cardId-$cardName.xml", $sCardData);

                $sComments = getComments($cardId);

                $p = xml_parser_create();
                xml_parse_into_struct($p, $sComments, $aComments, $index);
                xml_parser_free($p);
                if (count($aComments) > 1) {
                    file_put_contents("xml_dump/card-$projectId-$projectName-$cardId-$cardName-comments.xml", $sComments);
                    echo $projectName . ' ' . $cardName . ' comments' . "\n";
                }

                $sAttachmentInfo = getAttachmentInfo($cardId);

                $p = xml_parser_create();
                xml_parse_into_struct($p, $sAttachmentInfo, $aAttachmentInfo, $index);
                xml_parser_free($p);
                if (count($aAttachmentInfo) > 1) {

                    file_put_contents("xml_dump/card-$projectId-$projectName-$cardId-$cardName-attachment_info.xml", $sAttachmentInfo);
                    foreach ($aAttachmentInfo as $aAttachmentInf) {
                        if ($aAttachmentInf['attributes']) {
                            $attachmentId   = $aAttachmentInf['attributes']['NS:ATTACHMENTID'];
                            $attachmentName = $aAttachmentInf['attributes']['NS:FILENAME'];
                            echo $projectName . ' ' . $cardName . ' ' . $attachmentName . "\n";

                            $sAttachment = getAttachment($attachmentId);
                            file_put_contents("xml_dump/card-$projectId-$projectName-$cardId-$cardName-attachment-$attachmentId.xml", $sAttachment);

                            $p = xml_parser_create();
                            xml_parse_into_struct($p, $sAttachment, $aAttachment, $index);
                            xml_parser_free($p);

                            file_put_contents("xml_dump/card-$projectId-$projectName-$cardId-$cardName-attachment-$attachmentId-$attachmentName", base64_decode($aAttachment[1]['value']));
                        }
                    }
                }
            }
        }
    }
}
