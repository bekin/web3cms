<?php

// i18n - Russian Vice-Versa Language Pack (Access Denied)
$retval=array(
    'company/grid' => '������ ��������. ��������, �� � ��� ������������ ���� ��� �������������� ��������.',
    'companyPayment/grid' => '������ ��������. ��������, �� � ��� ������������ ���� ��� �������������� �������� ��������.',
    'expense/grid' => '������ ��������. ��������, �� � ��� ������������ ���� ��� �������������� ��������.',
    'invoice/grid' => '������ ��������. ��������, �� � ��� ������������ ���� ��� �������������� ������.',
    'project/grid' => '������ ��������. ��������, �� � ��� ������������ ���� ��� �������������� ��������.',
    'task/grid' => '������ ��������. ��������, �� � ��� ������������ ���� ��� �������������� �����.',
    'time/grid' => '������ ��������. ��������, �� � ��� ������������ ���� ��� �������������� ������� �������.',
    'user/grid' => '������ ��������. ��������, �� � ��� ������������ ���� ��� �������������� ����������.',
);
$myfile=dirname(dirname(dirname(__FILE__))).DIRECTORY_SEPARATOR.'mycustom'.DIRECTORY_SEPARATOR.basename(dirname(dirname(__FILE__))).DIRECTORY_SEPARATOR.basename(dirname(__FILE__)).DIRECTORY_SEPARATOR.basename(__FILE__);
return (file_exists($myfile) && is_array($myarray=require($myfile))) ? CMap::mergeArray($retval,$myarray) : $retval;