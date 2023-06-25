<?php

use Slim\App;
use Slim\Http\Request;
use Slim\Http\Response;

return function (App $app) {
    $container = $app->getContainer();

    $app->get('/[{name}]', function (Request $request, Response $response, array $args) use ($container) {
        // Sample log message
        $container->get('logger')->info("Slim-Skeleton '/' route");

        // Render index view
        return $container->get('renderer')->render($response, 'index.phtml', $args);
    });

    $app->get("/api/v1/penginap/", function (Request $request, Response $response) {
        $sql = "SELECT * FROM penginap";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetchAll();
        return $response->withJson(["status" => "success", "data" => $result], 200);
    });

    $app->get("/api/v1/penginap/{id}", function (Request $request, Response $response, $args) {
        $id = $args['id'];

        $sql = "SELECT * FROM penginap WHERE id_penginap = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        $result = $stmt->fetchAll();

        return $response->withJson(["status" => "success", "data" => $result], 200);
    });

    $app->put("/api/v1/penginap/{id}", function (Request $request, Response $response, $args) {
        $id = $args['id'];
        $contentType = $request->getHeaderLine('Content-Type');
        $data = $request->getBody()->getContents();
    
        if (empty($data)) {
            return $response->withJson(["status" => "failed", "data" => "No data provided"], 400);
        }
    
        if (strpos($contentType, 'application/json') !== false) {
            // Data dalam format JSON
            $data = json_decode($data, true);
            if (!is_array($data)) {
                return $response->withJson(["status" => "failed", "data" => "Invalid JSON data"], 400);
            }
        } elseif (strpos($contentType, 'application/x-www-form-urlencoded') !== false) {
            // Data dalam format URL form-encoded
            parse_str($data, $data);
            if (!is_array($data)) {
                return $response->withJson(["status" => "failed", "data" => "Invalid URL form-encoded data"], 400);
            }
        } else {
            return $response->withJson(["status" => "failed", "data" => "Unsupported data format"], 400);
        }
    
        // Memeriksa apakah data yang dibutuhkan ada
        $requiredFields = ['nama_penginap', 'id_kamar', 'durasi', 'check_in'];
        foreach ($requiredFields as $field) {
            if (!array_key_exists($field, $data)) {
                return $response->withJson(["status" => "failed", "data" => "Missing required field: $field"], 400);
            }
        }
    
        // Update data di database
        $sql = "UPDATE penginap SET nama_penginap = :nama_penginap, id_kamar = :id_kamar, durasi = :durasi, check_in = :check_in WHERE id_penginap = :id";
        $stmt = $this->db->prepare($sql);
    
        $stmt->bindValue(':nama_penginap', $data['nama_penginap']);
        $stmt->bindValue(':id_kamar', $data['id_kamar']);
        $stmt->bindValue(':durasi', $data['durasi']);
        $stmt->bindValue(':check_in', $data['check_in']);
        $stmt->bindValue(':id', $id);
    
        if ($stmt->execute())
            return $response->withJson(["status" => "success", "data" => "1"], 200);
    
        return $response->withJson(["status" => "failed", "data" => "0"], 200);
    });
    
    $app->post("/api/v1/penginap/", function (Request $request, Response $response) {
        $contentType = $request->getHeaderLine('Content-Type');
        $data = $request->getBody()->getContents();
    
        if (empty($data)) {
            return $response->withJson(["status" => "failed", "data" => "No data provided"], 400);
        }
    
        if (strpos($contentType, 'application/json') !== false) {
            // Data dalam format JSON
            $data = json_decode($data, true);
            if (!is_array($data)) {
                return $response->withJson(["status" => "failed", "data" => "Invalid JSON data"], 400);
            }
        } elseif (strpos($contentType, 'application/x-www-form-urlencoded') !== false) {
            // Data dalam format URL form-encoded
            parse_str($data, $data);
            if (!is_array($data)) {
                return $response->withJson(["status" => "failed", "data" => "Invalid URL form-encoded data"], 400);
            }
        } else {
            return $response->withJson(["status" => "failed", "data" => "Unsupported data format"], 400);
        }
    
        // Memeriksa apakah data yang dibutuhkan ada
        $requiredFields = ['id_penginap', 'nama_penginap', 'id_kamar', 'durasi', 'check_in'];
        foreach ($requiredFields as $field) {
            if (!array_key_exists($field, $data)) {
                return $response->withJson(["status" => "failed", "data" => "Missing required field: $field"], 400);
            }
        }
    
        // Insert data ke database
        $sql = "INSERT INTO penginap (id_penginap, nama_penginap, id_kamar, durasi, check_in) VALUES (:id_penginap, :nama_penginap, :id_kamar, :durasi, :check_in)";
        $stmt = $this->db->prepare($sql);
    
        $stmt->bindValue(':id_penginap', $data['id_penginap']);
        $stmt->bindValue(':nama_penginap', $data['nama_penginap']);
        $stmt->bindValue(':id_kamar', $data['id_kamar']);
        $stmt->bindValue(':durasi', $data['durasi']);
        $stmt->bindValue(':check_in', $data['check_in']);
    
        if ($stmt->execute())
            return $response->withJson(["status" => "success", "data" => "1"], 200);
    
        return $response->withJson(["status" => "failed", "data" => "0"], 200);
    });

    $app->delete("/api/v1/penginap/{id}", function (Request $request, Response $response, $args) {
        $id = $args['id'];

        $sql = "DELETE FROM penginap WHERE id_penginap = :id";
        $stmt = $this->db->prepare($sql);
        $data = [
            ":id" => $id
        ];

        if ($stmt->execute($data))
            return $response->withJson(["status" => "success", "data" => "1"], 200);

        return $response->withJson(["status" => "failed", "data" => "0"], 200);
    });
};
