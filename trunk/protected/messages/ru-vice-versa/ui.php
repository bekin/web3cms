<?php

// i18n - Russian Original Language Pack (CSS Themes)
$retval=array(
    'Black tie' => '�������� ������',
    'Blitzer' => '�������',
    'Cupertino' => '���������',
    'Dark hive' => '������ ����',
    'Dot luv' => '��� ���',
    'Eggplant' => '��������',
    'Excite bike' => '������������� ��������',
    'Flick' => '������',
    'Hot sneaks' => '������� ���������',
    'Humanity' => '������������',
    'Le frog' => '�������',
    'Mint choc' => '������� � ��������',
    'Overcast' => '��������',
    'Pepper grinder' => '������� �����',
    'Redmond' => '�������',
    'Smoothness' => '�������',
    'South street' => '����� �����',
    'Start' => '�����',
    'Sunny' => '���������',
    'Swanky purse' => '�������� ������',
    'Trontastic' => '���� � ����',
    'UI Darkness' => '�� Ҹ����',
    'UI Lightness' => '�� �������',
    'Vader' => '������',
);
$myfile=dirname(dirname(dirname(__FILE__))).DIRECTORY_SEPARATOR.'mycustom'.DIRECTORY_SEPARATOR.basename(dirname(dirname(__FILE__))).DIRECTORY_SEPARATOR.basename(dirname(__FILE__)).DIRECTORY_SEPARATOR.basename(__FILE__);
return (file_exists($myfile) && is_array($myarray=require($myfile))) ? CMap::mergeArray($retval,$myarray) : $retval;