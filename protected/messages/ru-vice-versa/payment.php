<?php

// i18n - Russian Vice-Versa Language Pack (Payment)
$retval=array(
    'Balance' => '������',
    'Cash' => '��������',
    'Check' => '���',
    'Credit card' => '��������� ��������',
    'Credit[accounting]' => '������',
    'Debit' => '�����',
    'Method' => '������',
    'Number' => '�����',
    'Paypal' => 'Paypal',
    'Wire' => '�������',
);
$myfile=dirname(dirname(dirname(__FILE__))).DIRECTORY_SEPARATOR.'_local'.DIRECTORY_SEPARATOR.basename(dirname(dirname(__FILE__))).DIRECTORY_SEPARATOR.basename(dirname(__FILE__)).DIRECTORY_SEPARATOR.basename(__FILE__);
return (file_exists($myfile) && is_array($myarray=require($myfile))) ? CMap::mergeArray($retval,$myarray) : $retval;