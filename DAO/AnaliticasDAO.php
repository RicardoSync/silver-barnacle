<?php
require_once __DIR__ . '/../includes/config.php';

class AnaliticasDAO {
    private $con;

    public function __construct() {
        $this->con = (new Conexion())->conectar();
    }

    public function getTrafico($mikrotik_id, $interface = null, $horas = 4) {
        $query = "SELECT interface, rx_bits, tx_bits, fecha_registro 
                  FROM historico_trafico 
                  WHERE mikrotik_id = :mikrotik_id 
                  AND fecha_registro >= DATE_SUB(NOW(), INTERVAL :horas HOUR) ";
        if ($interface) {
            $query .= "AND interface = :interface ";
        }
        $query .= "ORDER BY fecha_registro ASC";
        
        $stmt = $this->con->prepare($query);
        $stmt->bindParam(':mikrotik_id', $mikrotik_id, PDO::PARAM_INT);
        $stmt->bindParam(':horas', $horas, PDO::PARAM_INT);
        if ($interface) {
            $stmt->bindParam(':interface', $interface, PDO::PARAM_STR);
        }
        $stmt->execute();
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $this->downsamplePHP($data, $this->getIntervalForHours($horas), 'interface');
    }

    public function getPing($mikrotik_id, $horas = 4) {
        $query = "SELECT tipo, ms, fecha_registro 
                  FROM historico_pings 
                  WHERE mikrotik_id = :mikrotik_id 
                  AND fecha_registro >= DATE_SUB(NOW(), INTERVAL :horas HOUR) 
                  ORDER BY fecha_registro ASC";
        $stmt = $this->con->prepare($query);
        $stmt->bindParam(':mikrotik_id', $mikrotik_id, PDO::PARAM_INT);
        $stmt->bindParam(':horas', $horas, PDO::PARAM_INT);
        $stmt->execute();
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $this->downsamplePHP($data, $this->getIntervalForHours($horas), 'tipo');
    }

    public function getPingEquipo($equipo_id, $horas = 4) {
        $query = "SELECT ms, fecha_registro 
                  FROM historico_pings_equipos 
                  WHERE equipo_id = :equipo_id 
                  AND fecha_registro >= DATE_SUB(NOW(), INTERVAL :horas HOUR) 
                  ORDER BY fecha_registro ASC";
        $stmt = $this->con->prepare($query);
        $stmt->bindParam(':equipo_id', $equipo_id, PDO::PARAM_INT);
        $stmt->bindParam(':horas', $horas, PDO::PARAM_INT);
        $stmt->execute();
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $this->downsamplePHP($data, $this->getIntervalForHours($horas));
    }

    public function getRecursos($mikrotik_id, $horas = 4) {
        $query = "SELECT cpu_uso, ram_total, ram_libre, fecha_registro 
                  FROM historico_recursos 
                  WHERE mikrotik_id = :mikrotik_id 
                  AND fecha_registro >= DATE_SUB(NOW(), INTERVAL :horas HOUR) 
                  ORDER BY fecha_registro ASC";
        $stmt = $this->con->prepare($query);
        $stmt->bindParam(':mikrotik_id', $mikrotik_id, PDO::PARAM_INT);
        $stmt->bindParam(':horas', $horas, PDO::PARAM_INT);
        $stmt->execute();
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $this->downsamplePHP($data, $this->getIntervalForHours($horas));
    }

    public function getTopCaidas($horas = 4) {
        $query = "SELECT nombre_nodo, tipo_nodo, COUNT(*) as total_caidas, SUM(duracion_minutos) as total_minutos 
                  FROM historial_caidas 
                  WHERE fecha_caida >= DATE_SUB(NOW(), INTERVAL :horas HOUR) 
                  GROUP BY nombre_nodo, tipo_nodo 
                  ORDER BY total_caidas DESC 
                  LIMIT 10";
        $stmt = $this->con->prepare($query);
        $stmt->bindParam(':horas', $horas, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getInterfacesUnicas($mikrotik_id) {
        $query = "SELECT DISTINCT interface FROM historico_trafico WHERE mikrotik_id = :mikrotik_id";
        $stmt = $this->con->prepare($query);
        $stmt->bindParam(':mikrotik_id', $mikrotik_id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    private function getIntervalForHours($horas) {
        if ($horas <= 4) return 0;
        if ($horas <= 24) return 5;
        if ($horas <= 72) return 30;
        return 60;
    }

    private function downsamplePHP($data, $intervalMinutes, $groupByCol = null) {
        if (empty($data) || $intervalMinutes <= 1) return $data;

        $grouped = [];
        $intervalSeconds = $intervalMinutes * 60;

        foreach ($data as $row) {
            $timestamp = strtotime($row['fecha_registro']);
            $bucket = floor($timestamp / $intervalSeconds) * $intervalSeconds;
            $groupKey = (string)$bucket;
            
            if ($groupByCol && isset($row[$groupByCol])) {
                $groupKey .= '_' . $row[$groupByCol];
            }
            
            if (!isset($grouped[$groupKey])) {
                $grouped[$groupKey] = [
                    'count' => 0,
                    'sums' => [],
                    'fecha_registro' => date('Y-m-d H:i:s', $bucket)
                ];
                foreach ($row as $k => $v) {
                    if ($k !== 'fecha_registro' && is_numeric($v)) {
                        $grouped[$groupKey]['sums'][$k] = 0;
                    } elseif ($k !== 'fecha_registro') {
                        $grouped[$groupKey][$k] = $v;
                    }
                }
            }
            
            $grouped[$groupKey]['count']++;
            foreach ($row as $k => $v) {
                if ($k !== 'fecha_registro' && is_numeric($v)) {
                    $grouped[$groupKey]['sums'][$k] += (float)$v;
                }
            }
        }

        $result = [];
        foreach ($grouped as $g) {
            $item = ['fecha_registro' => $g['fecha_registro']];
            foreach ($g['sums'] as $k => $sum) {
                // Return integers for traffic, float for ms
                $item[$k] = round($sum / $g['count'], 2);
            }
            foreach ($g as $k => $v) {
                if ($k !== 'count' && $k !== 'sums' && $k !== 'fecha_registro') {
                    $item[$k] = $v;
                }
            }
            $result[] = $item;
        }

        // Sort by fecha_registro ascending
        usort($result, function($a, $b) {
            return strtotime($a['fecha_registro']) - strtotime($b['fecha_registro']);
        });

        return $result;
    }
}
?>
