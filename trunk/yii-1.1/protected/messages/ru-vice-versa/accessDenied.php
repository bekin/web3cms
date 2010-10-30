<?php

// i18n - Russian Vice-Versa Language Pack (Access Denied)
$retval=array(
    'company/create' => '������ ��������. ��������, �� � ��� ������������ ���� ��� �������� ����� ������ ��������.',
    'company/grid' => '������ ��������. ��������, �� � ��� ������������ ���� ��� �������������� ��������.',
    'company/show' => '������ ��������. ��������, �� � ��� ������������ ���� ��� ��������� ������ �������� ����� {id}.',
    'company/update' => '������ ��������. ��������, �� � ��� ������������ ���� ��� �������������� ������ ��������.',
    'companyPayment/create' => '������ ��������. ��������, �� � ��� ������������ ���� ��� �������� ����� ������ ������� ��������.',
    'companyPayment/grid' => '������ ��������. ��������, �� � ��� ������������ ���� ��� �������������� �������� ��������.',
    'companyPayment/show' => '������ ��������. ��������, �� � ��� ������������ ���� ��� ��������� ������ ������� �������� ����� {id}.',
    'companyPayment/update' => '������ ��������. ��������, �� � ��� ������������ ���� ��� �������������� ������ ������� ��������.',
    'expense/create' => '������ ��������. ��������, �� � ��� ������������ ���� ��� �������� ����� ������ �������.',
    'expense/delete' => '������ ��������. ��������, �� � ��� ������������ ���� ��� �������� ������ �������.',
    'expense/deleteWhenInvoiceIsSet' => '������ ��������. ��������, �� � ��� ������������ ���� ��� �������� ������ ������� ���������� �� ������.',
    'expense/grid' => '������ ��������. ��������, �� � ��� ������������ ���� ��� �������������� ��������.',
    'expense/show' => '������ ��������. ��������, �� � ��� ������������ ���� ��� ��������� ������ ������� ����� {id}.',
    'expense/update' => '������ ��������. ��������, �� � ��� ������������ ���� ��� �������������� ������ �������.',
    'expense/updateWhenInvoiceIsSet' => '������ ��������. ��������, �� � ��� ������������ ���� ��� �������������� ������ ������� ���������� �� ������.',
    'invoice/create' => '������ ��������. ��������, �� � ��� ������������ ���� ��� �������� ����� ������ �����.',
    'invoice/grid' => '������ ��������. ��������, �� � ��� ������������ ���� ��� �������������� ������.',
    'invoice/show' => '������ ��������. ��������, �� � ��� ������������ ���� ��� ��������� ������ ����� ����� {id}.',
    'invoice/update' => '������ ��������. ��������, �� � ��� ������������ ���� ��� �������������� ������ �����.',
    'project/create' => '������ ��������. ��������, �� � ��� ������������ ���� ��� �������� ����� ������ �������.',
    'project/grid' => '������ ��������. ��������, �� � ��� ������������ ���� ��� �������������� ��������.',
    'project/show' => '������ ��������. ��������, �� � ��� ������������ ���� ��� ��������� ������ ������� ����� {id}.',
    'project/update' => '������ ��������. ��������, �� � ��� ������������ ���� ��� �������������� ������ �������.',
    'task/create' => '������ ��������. ��������, �� � ��� ������������ ���� ��� �������� ����� ������ ������.',
    'task/grid' => '������ ��������. ��������, �� � ��� ������������ ���� ��� �������������� �����.',
    'task/show' => '������ ��������. ��������, �� � ��� ������������ ���� ��� ��������� ������ ������ ����� {id}.',
    'task/update' => '������ ��������. ��������, �� � ��� ������������ ���� ��� �������������� ������ ������.',
    'time/create' => '������ ��������. ��������, �� � ��� ������������ ���� ��� �������� ����� ������ �������.',
    'time/delete' => '������ ��������. ��������, �� � ��� ������������ ���� ��� �������� ������ �������.',
    'time/deleteWhenInvoiceIsSet' => '������ ��������. ��������, �� � ��� ������������ ���� ��� �������� ������ ������� ��������� �� ������.',
    'time/grid' => '������ ��������. ��������, �� � ��� ������������ ���� ��� �������������� ������� �������.',
    'time/show' => '������ ��������. ��������, �� � ��� ������������ ���� ��� ��������� ������ ������� ����� {id}.',
    'time/update' => '������ ��������. ��������, �� � ��� ������������ ���� ��� �������������� ������ �������.',
    'time/updateWhenInvoiceIsSet' => '������ ��������. ��������, �� � ��� ������������ ���� ��� �������������� ������ ������� ��������� �� ������.',
    'user/create' => '������ ��������. ��������, �� � ��� ������������ ���� ��� �������� ����� ������� ������.',
    'user/grid' => '������ ��������. ��������, �� � ��� ������������ ���� ��� �������������� ����������.',
    'user/show' => '������ ��������. ��������, �� � ��� ������������ ���� ��� ��������� ������� ������ ����� {id}.',
    'user/update' => '������ ��������. ��������, �� � ��� ������������ ���� ��� �������������� ������� ������.',
    'user/updateInterface' => '������ ��������. ��������, �� � ��� ������������ ���� ��� ��������� �������� ���� ��� ������� ������.',
);
$myfile=dirname(dirname(dirname(__FILE__))).DIRECTORY_SEPARATOR.'mycustom'.DIRECTORY_SEPARATOR.basename(dirname(dirname(__FILE__))).DIRECTORY_SEPARATOR.basename(dirname(__FILE__)).DIRECTORY_SEPARATOR.basename(__FILE__);
return (file_exists($myfile) && is_array($myarray=require($myfile))) ? CMap::mergeArray($retval,$myarray) : $retval;