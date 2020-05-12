<?php

require 'vendor/autoload.php';

use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Output\ConsoleOutput;

// Handle both CLI and webserver
$is_cli = ((php_sapi_name() === 'cli') ? true : false);
$eol = ($is_cli ? PHP_EOL : '<br/>');
$space = ($is_cli ? ' ' : '&nbsp;');
if ($is_cli) {
    $is_logging = boolval(getenv('logging'));
} else {
    parse_str($_SERVER['QUERY_STRING'], $query);
    $is_logging = boolval($query['logging']);
}

/**
 * Print all unhandled exceptions
 *
 * @param mixed $exception Exception that occurred
 */
function unhandled_exception_handler($exception) {
    print 'Unhandled Exception: ' . $exception->getMessage() . $eol
        . $space . $space
        . str_replace(PHP_EOL,
                      $eol . $space . $space,
                      $exception->getTraceAsString());
}
set_exception_handler('unhandled_exception_handler');

// Connect to the MariaDB from within the Docker network
$db = new mysqli('mariadb', 'fero', 'feropw') or die('Could not connect:' . mysqli_error());

// Perform a simple SELCT query to get all the tables
// if ($result = $db->query('SELECT * FROM information_schema.tables')) {
if ($result = $db->query('SELECT VERSION() as version')) {
    if ($is_cli) {
        $table = new Table(new ConsoleOutput());
    } else {
        print '<table style="border: 1px solid black; text-align: center;">';
    }
    $keys = NULL;
    while($row = $result->fetch_assoc()) {
        if (is_null($keys)) {
            $keys = array_keys($row);
            if ($is_cli) {
                $table->setHeaders($keys);
            } else {
                print '<tr>';
                foreach($keys as $key) {
                    print '<th style="border: 1px solid black; padding: 10px;">' . $key . '</th>';
                }
                print '</tr>';
            }
        }
        if ($is_cli) {
            $table->addRow($row);
        } else {
            print '<tr>';
            foreach($keys as $key) {
                print '<td style="border: 1px solid black; padding: 10px;">' . $row[$key] . '</td>';
            }
            print '</tr>';
        }
    }
    if ($is_cli) {
        $table->render();
    } else {
        print '</table>';
    }
    $result->close();
} else {
    print 'Unable to retrieve information_schema.tables' . $eol;
}
$db->close();

?>