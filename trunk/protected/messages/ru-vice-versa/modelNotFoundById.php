<?php

// i18n - Russian Vice-Versa Language Pack (Model Not Found By Id)
$retval=array(
    'company' => '��������, �� ������ �������� ����� {id} �� ����� ���� ������� � ���� ������. �������� ��������� �� ����������.',
    'companyPayment' => '��������, �� ������ ������� �������� ����� {id} �� ����� ���� ������� � ���� ������. �������� ��������� �� ����������.',
    'expense' => '��������, �� ������ ������� ����� {id} �� ����� ���� ������� � ���� ������. �������� ��������� �� ����������.',
    'invoice' => '��������, �� ������ ����� ����� {id} �� ����� ���� ������� � ���� ������. �������� ��������� �� ����������.',
    'project' => '��������, �� ������ ������� ����� {id} �� ����� ���� ������� � ���� ������. �������� ��������� �� ����������.',
    'task' => '��������, �� ������ ������ ����� {id} �� ����� ���� ������� � ���� ������. �������� ��������� �� ����������.',
    'time' => '��������, �� ������ ������� ����� {id} �� ����� ���� ������� � ���� ������. �������� ��������� �� ����������.',
    'user' => '��������, �� ������� ������ ����� {id} �� ����� ���� ������� � ���� ������. �������� ��������� �� ����������.',
);
$myfile=dirname(dirname(dirname(__FILE__))).DIRECTORY_SEPARATOR.'mycustom'.DIRECTORY_SEPARATOR.basename(dirname(dirname(__FILE__))).DIRECTORY_SEPARATOR.basename(dirname(__FILE__)).DIRECTORY_SEPARATOR.basename(__FILE__);
return (file_exists($myfile) && is_array($myarray=require($myfile))) ? CMap::mergeArray($retval,$myarray) : $retval;