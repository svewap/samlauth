<?php
/*
 * Copyright 2014 Google Inc.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

/*
function lihghtsaml_autoload($className)
{
    $classPath = explode('\\', $className);
    if ($classPath[0] != 'LightSaml') {
        return;
    }
    $classPath = array_slice($classPath, 1);
    $filePath = dirname(__FILE__) . '/LightSaml/' . implode('/', $classPath) . '.php';
    if (file_exists($filePath)) {
        require_once($filePath);
    }
}

spl_autoload_register('lihghtsaml_autoload');
*/

function saml2_autoload($className)
{
    $classPath = explode('\\', $className);
    if ($classPath[0] != 'OneLogin') {
        return;
    }
    $classPath = array_slice($classPath, 2);
    $filePath = dirname(__FILE__) . '/Saml2/' . implode('/', $classPath) . '.php';
    if (file_exists($filePath)) {
        require_once($filePath);
    }
}

spl_autoload_register('saml2_autoload');



function xmlseclibs_autoload($className)
{
    if (strpos($className, 'RobRichards\XMLSecLibs') !== 0) {
        return;
    }
    $classPath = explode('\\', $className);
    $classPath = array_slice($classPath, 2);
    $filePath = dirname(__FILE__). '/xmlseclibs/src/' . implode('/', $classPath) . '.php';
    if (file_exists($filePath)) {
        require_once($filePath);
    }
}

spl_autoload_register('xmlseclibs_autoload');