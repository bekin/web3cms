<?php

// i18n - Russian Original Language Pack (Emails)
$retval=array(
    'New member account' => '����� ������� ������',
    'Content(New member account)' => '����� ���������� � {siteTitle}

����� ������� ������ "{screenName}" ���� ������� �������.

--------------------------------------------------
���� �������������: {emailConfirmationKey}
--------------------------------------------------

����� ����������� ��� ����� �����, ���������� �������� ��������� ������
{emailConfirmationLink}',
);
$myfile=dirname(dirname(dirname(__FILE__))).DIRECTORY_SEPARATOR.'mycustom'.DIRECTORY_SEPARATOR.basename(dirname(dirname(__FILE__))).DIRECTORY_SEPARATOR.basename(dirname(__FILE__)).DIRECTORY_SEPARATOR.basename(__FILE__);
return (file_exists($myfile) && is_array($myarray=require($myfile))) ? CMap::mergeArray($retval,$myarray) : $retval;