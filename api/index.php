<!--
                           _    _______          _     
                          | |  |__   __\        | |    
                     _ __ | |_ __ | | ___   ___ | |___ 
                    | '_ \| | '_ \| |/ _ \ / _ \| / __|
                    | | | | | |_) | | (_) | (_) | \__ \
                    |_| |_|_| .__/|_|\___/ \___/|_|___/
 ___________________________| |_________________________________________
|                           |_|                                        |\
|                                                                      |_\
|   File    : index.php                                                   |
|   Created : 08-Mar-2013                                                 |
|   By      : atrilla                                                     |
|                                                                         |
|   nlpTools - Natural Language Processing Toolkit for PHP                |
|                                                                         |
|   Copyright (c) 2013 Alexandre Trilla                                   |
|                                                                         |
|   ___________________________________________________________________   |
|                                                                         |
|   This file is part of nlpTools.                                        |
|                                                                         |
|   nlpTools is free software: you can redistribute it and/or modify      |
|   it under the terms of the MIT/X11 License as published by the         |
|   Massachusetts Institute of Technology. See the MIT/X11 License        |
|   for more details.                                                     |
|                                                                         |
|   You should have received a copy of the MIT/X11 License along with     |
|   this source code distribution of nlpTools (see the COPYING file       |
|   in the root directory). If not, see                                   |
|   <http://www.opensource.org/licenses/mit-license>.                     |
|_________________________________________________________________________|
-->

<?php

// service, text

require(dirname(__FILE__)."/RestUtils.php");
include(dirname(__FILE__).
    "/../core/classification/MultinomialNaiveBayes.php");
include_once("DB.php");
include_once(dirname(__FILE__)."/../util/dbauth/DBAuthManager.php");

$maxHits = 500;

//PERMISSIONS
$granted  = true;
$callerIP = $_SERVER['REMOTE_ADDR'];
$db = DB::connect(getConnection().getPrefixID().'_NLPToolsAPI');
if (DB::isError($db)) {
   throw new Exception("REST API: ".
        "DB connection error! ".$db->getMessage()."\n");
} else {
    // check if it exists
    // Table schema: IPaddress Hits
    $itExists = $db->getOne("SELECT Hits FROM Users WHERE IPaddress = '$callerIP';");
    if (!is_null($itExists)) {
        // check range
        if ($itExists >= $maxHits) {
            // not allowed, limit reached
            $granted = false;
        } else {
            // allowed, increase hits
            $newHits = $itExists + 1;
            $db->query("UPDATE Users SET Hits = '$newHits' WHERE IPaddress = '$callerIP';");
        }
    } else {
        // create new user
        $db->query("INSERT INTO Users VALUES ( '$callerIP', 1 );");
    }
}
if ($granted) {
    $data = RestUtils::processRequest();
    switch($data->getMethod())  
    {
        case 'post': // right method
            $serv = $data->getService();
            $tex = $data->getTextToProc();
            $classifier = new MultinomialNaiveBayes();
            if ($serv == 'sentiment_news') {
                $classifier->setDatabase("semeval07");
            }
            $lab = $classifier->classify($tex);
            $res = array('label' => $lab);
            RestUtils::sendResponse(200, json_encode($res), 'application/json');
            break;
        default: // incorrect method
            RestUtils::sendResponse(400);
    }
} else {
    RestUtils::sendResponse(401);
}

?>
