<?php

use XrechnungKit\XRechnungValidator;

$validator = new XRechnungValidator();

// Validate the file in-place. The generator already ran XSD validation in
// memory before writing, so this is a defensive second pass against the
// committed bytes.
$isValid = $validator->validate('out/Demo-Invoice-001.xml');

if ($isValid) {
    echo "PASS: UBL XSD valid\n";
} else {
    echo "FAIL: file landed at *_invalid.xml\n";
    foreach ($validator->getErrors() as $error) {
        echo "  - {$error}\n";
    }
}

// Optional: KoSIT Schematron (German federal business rules: BR-DE-* + CIUS).
// Requires Java 17+ at validation time. Returns the same boolean contract.
$schematronOk = $validator->validateSchematron('out/Demo-Invoice-001.xml');
