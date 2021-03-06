<?php
require __DIR__ . '/RedBean/rb.php';

R::setup('sqlite:./database.db');

// freeze, if database is set up and first entries are stored!
// R::freeze(true);

require 'Slim/Slim.php';
\Slim\Slim::registerAutoloader();
$app = new \Slim\Slim();

/**
 * get a list of entries
 */
$app->get('/list', function () use ($app) {

		$entries = R::findAll('entry');

		$rows = array();
		foreach ($entries as $entry) {
			$rows[] = array(
				'id' => $entry->id,
				'name' => $entry->name,
				'amount' => $entry->amount
			);
		}

		$app->response->headers->set('Content-Type', 'application/json');
		$app->response->setBody(json_encode($rows));
	}
);
/**
 * Add one entry
 */
$app->post('/add', function () use ($app) {

		$response = array();
		$body = json_decode($app->request->getBody(), true);

		$name = filter_var($body['name'], FILTER_SANITIZE_STRING);
		$amount = (int)filter_var($body['amount'], FILTER_SANITIZE_STRING);
		$hash = sha1($name . $amount);

		//check if this entry already exists
		$existing = R::find('entry', 'hash = ?', array($hash));
		if (!$existing) {
			$newEntry = R::dispense('entry');
			$newEntry->hash = $hash;
			$newEntry->name = $name;
			$newEntry->amount = $amount;

			try {
				$id = R::store($newEntry);
				$response = array("id" => $id, "name" => $name, "amount" => $amount);
			} catch (Exception $e) {
				$response = array();
			}
		}

		$app->response->headers->set('Content-Type', 'application/json');
		$app->response->setBody(json_encode($response));
	}
);

$app->post('/delete', function () use ($app) {

		$json = json_decode($app->request->getBody(), TRUE);
		$todelete = $json['delete'];
		$response = array("status" => "ok");

		$entries = R::loadAll('entry', $todelete);
		if ($entries) {
			try {
				R::trashAll($entries);
			} catch (Exception $e) {
				$response["status"] = "fail";
			}
		}

		$app->response->headers->set('Content-Type', 'application/json');
		$app->response->setBody(json_encode($response));
	}
);
$app->run();