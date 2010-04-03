<?php

// i18n - Russian Language Pack (System Messages)
$retval=array(
    'Available interfaces: {availableInterfaces}.' => '��������� ������� ����: {availableInterfaces}.',
    'Available languages: {availableLanguages}.' => '��������� �����: {availableLanguages}.',
    'Class {class} does not exist. Method called: {method}.' => '������ {class} �� ����������. ��������� �����: {method}.',
    'Could not delete the {model} model. Model ID: {modelId}. Method called: {method}.' => '�� ������� ������� ������ {model}. ID ������: {modelId}. ���������� �����: {method}.',
    'Could not load {model} model. Model ID: {modelId}. Method called: {method}.' => '�� ������� ��������� ������ {model}. ID ������: {modelId}. ���������� �����: {method}.',
    'Could not save attributes of the {model} model. Model ID: {modelId}. Method called: {method}.' => '�� ������� ��������� �������� ������ {model}. ID ������: {modelId}. ���������� �����: {method}.',
    'Failed creating UserDetails record. Member ID: {userId}. Method called: {method}.' => '�� ������� ������� ������ UserDetails. ID ���������: {userId}. ��������� �����: {method}.',
    'Incorrect parameter in method call: {method}.' => '�������� ��������� � ������ ������: {method}.',
    'Member with ID {userId} has no UserDetails record associated. Method called: {method}.' => '�������� � ID {userId} �� ����� ��������������� UserDetails ������. ��������� �����: {method}.',
    'Missing parameter in file params.php: {parameter}.' => '�������� �������� � ����� params.php: {parameter}.',
    'Unacceptable value of {parameter} system parameter: {value}. Method called: {method}.' => '������������ �������� {parameter} ���������� ���������: {value}. ��������� �����: {method}.',
    'Unacceptable values of layout constants... content: {content}, sidebar1: {sidebar1}, sidebar2: {sidebar2}, total: {total}. Method called: {method}.' => '������������ �������� �������� ������������... ����������: {content}, �������1: {sidebar1}, �������2: {sidebar2}, �����: {total}. ��������� �����: {method}.',
    'Unacceptable values of layout parameters... content: {content}, sidebar1: {sidebar1}, sidebar2: {sidebar2}, total: {total}. Method called: {method}.' => '������������ �������� ���������� ������������... ����������: {content}, �������1: {sidebar1}, �������2: {sidebar2}, �����: {total}. ��������� �����: {method}.',
    'Uncommon parameter in method call: {method}.' => '��������� ��������� � ������ ������: {method}.',
);
$myfile=dirname(dirname(dirname(__FILE__))).DIRECTORY_SEPARATOR.'mycustom'.DIRECTORY_SEPARATOR.basename(dirname(dirname(__FILE__))).DIRECTORY_SEPARATOR.basename(dirname(__FILE__)).DIRECTORY_SEPARATOR.basename(__FILE__);
return (file_exists($myfile) && is_array($myarray=require($myfile))) ? CMap::mergeArray($retval,$myarray) : $retval;