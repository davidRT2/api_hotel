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

    $app->get("/api/v1/penginap/nama/{nama}", function (Request $request, Response $response, $args) {
        $nama = $args['nama'];

        $sql = "SELECT * FROM penginap WHERE nama_penginap LIKE :nama";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':nama', '%' . $nama . '%');
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
        $requiredFields = ['id_penginap', 'nama_penginap', 'id_kamar', 'durasi', 'check_in', 'telepon'];
        foreach ($requiredFields as $field) {
            if (!array_key_exists($field, $data)) {
                return $response->withJson(["status" => "failed", "data" => "Missing required field: $field"], 400);
            }
        }

        // Validasi relasi ID kamar dengan tabel kamar
        $id_kamar = $data['id_kamar'];
        $sql = "SELECT COUNT(*) as count FROM kamar WHERE id_kamar = :id_kamar";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id_kamar', $id_kamar);
        $stmt->execute();
        $result = $stmt->fetch();
        if ($result['count'] == 0) {
            return $response->withJson(["status" => "failed", "data" => "Invalid kamar ID"], 400);
        }

        // Insert data ke tabel penginap
        $sqlInsert = "INSERT INTO penginap (id_penginap, nama_penginap, id_kamar, durasi, check_in, telepon) VALUES (:id_penginap, :nama_penginap, :id_kamar, :durasi, :check_in, :telepon)";
        $stmtInsert = $this->db->prepare($sqlInsert);

        $stmtInsert->bindValue(':id_penginap', $data['id_penginap']);
        $stmtInsert->bindValue(':nama_penginap', $data['nama_penginap']);
        $stmtInsert->bindValue(':id_kamar', $data['id_kamar']);
        $stmtInsert->bindValue(':durasi', $data['durasi']);
        $stmtInsert->bindValue(':check_in', $data['check_in']);
        $stmtInsert->bindValue(':telepon', $data['telepon']);

        // Update kolom id_penginap pada tabel kamar
        $sqlUpdate = "UPDATE kamar SET id_penginap = :id_penginap WHERE id_kamar = :id_kamar";
        $stmtUpdate = $this->db->prepare($sqlUpdate);

        $stmtUpdate->bindValue(':id_penginap', $data['id_penginap']);
        $stmtUpdate->bindValue(':id_kamar', $data['id_kamar']);

        try {
            $this->db->beginTransaction();

            if ($stmtInsert->execute() && $stmtUpdate->execute()) {
                $this->db->commit();
                return $response->withJson(["status" => "success", "data" => "1"], 200);
            }
        } catch (PDOException $e) {
            $this->db->rollBack();
            return $response->withJson(["status" => "failed", "data" => "Error executing SQL statement: " . $e->getMessage()], 500);
        }

        return $response->withJson(["status" => "failed", "data" => "0"], 200);
    });

    /**
     * Kamar Start
     */

    $app->get("/api/v1/kamar", function (Request $request, Response $response, $args) {
        // Retrieve all data from kamar table
        $sql = "SELECT k.*, t.nama_tipe, t.harga_per_malam
            FROM kamar k
            JOIN tipe t ON k.id_tipe = t.id_tipe";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $results = $stmt->fetchAll();

        if (empty($results)) {
            return $response->withJson(["status" => "failed", "data" => "No kamar found"], 404);
        }

        $responsePayload = [];
        foreach ($results as $result) {
            $id_tipe = $result['id_tipe'];

            // Retrieve list of fasilitas for each tipe
            $sql = "SELECT f.id_fasilitas, f.nama_fasilitas
                FROM fasilitas_tipe ft
                JOIN fasilitas f ON ft.id_fasilitas = f.id_fasilitas
                WHERE ft.id_tipe = :id_tipe";
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':id_tipe', $id_tipe);
            $stmt->execute();
            $fasilitas = $stmt->fetchAll();

            $kamarData = [
                "id_kamar" => $result['id_kamar'],
                "nama_tipe" => $result['nama_tipe'],
                "id_penginap" => $result['id_penginap'],
                "harga_per_malam" => $result['harga_per_malam'],
                "daftar_fasilitas" => $fasilitas
            ];

            $responsePayload[] = $kamarData;
        }

        return $response->withJson(["status" => "success", "data" => $responsePayload], 200);
    });

    $app->get("/api/v1/kamar/{id_kamar}", function (Request $request, Response $response, $args) {
        $id_kamar = $args['id_kamar'];

        // Retrieve data from kamar table
        $sql = "SELECT k.id_kamar, k.id_tipe, t.nama_tipe, t.harga_per_malam
                FROM kamar k
                JOIN tipe t ON k.id_tipe = t.id_tipe
                WHERE k.id_kamar = :id_kamar";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id_kamar', $id_kamar);
        $stmt->execute();
        $result = $stmt->fetch();

        if (!$result) {
            return $response->withJson(["status" => "failed", "data" => "Kamar not found"], 404);
        }

        $id_tipe = $result['id_tipe'];

        // Retrieve list of fasilitas for the given tipe
        $sql = "SELECT f.id_fasilitas, f.nama_fasilitas
                FROM fasilitas_tipe ft
                JOIN fasilitas f ON ft.id_fasilitas = f.id_fasilitas
                WHERE ft.id_tipe = :id_tipe";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id_tipe', $id_tipe);
        $stmt->execute();
        $fasilitas = $stmt->fetchAll();

        $responsePayload = [
            "id_kamar" => $result['id_kamar'],
            "nama_tipe" => $result['nama_tipe'],
            "harga_per_malam" => $result['harga_per_malam'],
            "daftar_fasilitas" => $fasilitas
        ];

        return $response->withJson(["status" => "success", "data" => $responsePayload], 200);
    });
    //add-Room
    $app->post("/api/v1/kamar", function (Request $request, Response $response) {
        $contentType = $request->getHeaderLine('Content-Type');
        $data = $request->getBody()->getContents();
    
        if (empty($data)) {
            return $response->withJson(["status" => "failed", "data" => "No data provided"], 400);
        }
    
        if (strpos($contentType, 'application/json') !== false) {
            // Data dalam format JSON
            $dataArray = json_decode($data, true);
            if (!is_array($dataArray)) {
                return $response->withJson(["status" => "failed", "data" => "Invalid JSON data"], 400);
            }
        } elseif (strpos($contentType, 'application/x-www-form-urlencoded') !== false) {
            // Data dalam format URL form-encoded
            parse_str($data, $dataArray);
            if (!is_array($dataArray)) {
                return $response->withJson(["status" => "failed", "data" => "Invalid URL form-encoded data"], 400);
            }
        } else {
            return $response->withJson(["status" => "failed", "data" => "Unsupported data format"], 400);
        }
    
        // Memeriksa apakah data yang dibutuhkan ada
        $requiredFields = ['id_kamar', 'id_tipe'];
    
        // Jika menerima banyak baris JSON, pastikan untuk melakukan iterasi pada setiap data
        foreach ($dataArray as $data) {
            foreach ($requiredFields as $field) {
                if (!array_key_exists($field, $data)) {
                    return $response->withJson(["status" => "failed", "data" => "Missing required field: $field"], 400);
                }
            }
    
            $sqlInsert = "INSERT INTO kamar (id_kamar, id_tipe) VALUES (:id_kamar, :id_tipe)";
            $stmtInsert = $this->db->prepare($sqlInsert);
    
            $stmtInsert->bindValue(':id_kamar', $data['id_kamar']);
            $stmtInsert->bindValue(':id_tipe', $data['id_tipe']);
    
            try {
                $this->db->beginTransaction();
    
                if ($stmtInsert->execute()) {
                    $this->db->commit();
                } else {
                    $this->db->rollBack();
                    return $response->withJson(["status" => "failed", "data" => "Failed to execute SQL statement"], 500);
                }
            } catch (PDOException $e) {
                $this->db->rollBack();
                return $response->withJson(["status" => "failed", "data" => "Error executing SQL statement: " . $e->getMessage()], 500);
            }
        }
    
        return $response->withJson(["status" => "success", "data" => "1"], 200);
    });
    

    /**
     * Kamar End
     */

    $app->delete("/api/v1/penginap/{id}", function (Request $request, Response $response, $args) {
        $id = $args['id'];

        // Get the id_kamar associated with the penginap to be deleted
        $sqlSelect = "SELECT id_kamar FROM penginap WHERE id_penginap = :id";
        $stmtSelect = $this->db->prepare($sqlSelect);
        $stmtSelect->bindValue(':id', $id);
        $stmtSelect->execute();
        $result = $stmtSelect->fetch();

        if (!$result) {
            return $response->withJson(["status" => "failed", "data" => "Penginap not found"], 404);
        }

        $id_kamar = $result['id_kamar'];

        // Delete the penginap from penginap table
        $sqlDelete = "DELETE FROM penginap WHERE id_penginap = :id";
        $stmtDelete = $this->db->prepare($sqlDelete);
        $stmtDelete->bindValue(':id', $id);

        // Update id_penginap column in kamar table to NULL for the associated id_kamar
        $sqlUpdate = "UPDATE kamar SET id_penginap = NULL WHERE id_kamar = :id_kamar";
        $stmtUpdate = $this->db->prepare($sqlUpdate);
        $stmtUpdate->bindValue(':id_kamar', $id_kamar);

        try {
            $this->db->beginTransaction();

            if ($stmtDelete->execute() && $stmtUpdate->execute()) {
                $this->db->commit();
                return $response->withJson(["status" => "success", "data" => "1"], 200);
            }
        } catch (PDOException $e) {
            $this->db->rollBack();
            return $response->withJson(["status" => "failed", "data" => "Error executing SQL statement: " . $e->getMessage()], 500);
        }

        return $response->withJson(["status" => "failed", "data" => "0"], 200);
    });

    $app->get("/api/v1/tipe", function (Request $request, Response $response, $args) {
        $sql = "SELECT * FROM tipe";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetchAll();
        return $response->withJson(["status" => "success", "data" => $result], 200);
    });
};
