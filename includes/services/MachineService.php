<?php
declare(strict_types=1);

final class MachineService {
    private PDO $pdo;

    public function __construct(PDO $pdo) { $this->pdo = $pdo; }

    /** LISTADO con filtros/paginación */
    public function list(array $opts = []): array {
        $page   = max(1, (int)($opts['page']   ?? 1));
        $limit  = min(50, max(1, (int)($opts['limit']  ?? 12)));
        $offset = ($page - 1) * $limit;
        $q      = trim((string)($opts['q']      ?? ''));
        $status = trim((string)($opts['status'] ?? ''));

        $where = [];
        $params = [];

        if ($q !== '') {
            $where[] = '(m.name LIKE ? OR m.model LIKE ? OR m.description LIKE ?)';
            $like = "%$q%";
            array_push($params, $like, $like, $like);
        }
        if ($status !== '' && in_array($status, ['published','draft','archived'], true)) {
            $where[] = 'm.status = ?';
            $params[] = $status;
        }
        $whereSQL = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM machines m $whereSQL");
        $stmt->execute($params);
        $total = (int)$stmt->fetchColumn();

        $items = [];
        if ($total > 0) {
            $sql = "
                SELECT
                  m.id, m.name, m.model, m.slug, m.status, m.featured, m.sort_order,
                  m.main_image, m.short_description,
                  m.created_at, m.updated_at
                FROM machines m
                $whereSQL
                ORDER BY m.created_at DESC
                LIMIT $limit OFFSET $offset
            ";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        return [
            'items'      => $items,
            'pagination' => ['page'=>$page, 'limit'=>$limit, 'total'=>$total],
        ];
    }

    /** OBTENER una máquina por id */
    public function get(int $id): ?array {
        $stmt = $this->pdo->prepare("
            SELECT
              id, name, model, slug, description, short_description,
              category_id, status, featured, sort_order, main_image,
              created_at, updated_at
            FROM machines WHERE id = ?
        ");
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /** CREAR máquina, devuelve id */
    public function create(array $data, int $userId): int {
        $name  = trim((string)($data['name']  ?? ''));
        $model = trim((string)($data['model'] ?? ''));
        $slug  = trim((string)($data['slug']  ?? ''));
        $desc  = trim((string)($data['description'] ?? ''));
        $short = trim((string)($data['short_description'] ?? ''));
        $status = in_array(($data['status'] ?? 'draft'), ['draft','published','archived'], true) ? $data['status'] : 'draft';
        $catId = ($data['category_id'] ?? null) ? (int)$data['category_id'] : null;
        $featured = !empty($data['featured']) ? 1 : 0;
        $sort = (int)($data['sort_order'] ?? 0);
        $img  = trim((string)($data['main_image'] ?? ''));

        if ($name === '') throw new InvalidArgumentException('El nombre es obligatorio');
        if ($slug === '')  $slug = $this->slugify($name);
        $slug = $this->uniqueSlug($slug, null);

        $sql = "INSERT INTO machines
                (name, model, slug, description, short_description,
                 category_id, status, featured, sort_order, main_image, created_by)
                VALUES (?,?,?,?,?,?,?,?,?,?,?)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            $name, $model, $slug, $desc, $short,
            $catId, $status, $featured, $sort, ($img ?: null), $userId
        ]);
        return (int)$this->pdo->lastInsertId();
    }

    /** ACTUALIZAR máquina */
    public function update(int $id, array $data, int $userId): bool {
        $row = $this->get($id);
        if (!$row) throw new RuntimeException('Máquina no encontrada');

        $name  = trim((string)($data['name']  ?? $row['name']));
        $model = trim((string)($data['model'] ?? $row['model']));
        $slug  = trim((string)($data['slug']  ?? $row['slug']));
        $desc  = trim((string)($data['description'] ?? $row['description']));
        $short = trim((string)($data['short_description'] ?? $row['short_description']));
        $status = in_array(($data['status'] ?? $row['status']), ['draft','published','archived'], true) ? $data['status'] : $row['status'];
        $catId = array_key_exists('category_id',$data) ? ( ($data['category_id']!=='') ? (int)$data['category_id'] : null ) : $row['category_id'];
        $featured = isset($data['featured']) ? (int)!!$data['featured'] : (int)$row['featured'];
        $sort = isset($data['sort_order']) ? (int)$data['sort_order'] : (int)$row['sort_order'];
        $img  = array_key_exists('main_image',$data) ? (trim((string)$data['main_image']) ?: null) : $row['main_image'];

        if ($name === '') throw new InvalidArgumentException('El nombre es obligatorio');
        if ($slug === '') $slug = $this->slugify($name);
        if ($slug !== $row['slug']) $slug = $this->uniqueSlug($slug, $id);

        $sql = "UPDATE machines SET
                  name=?, model=?, slug=?, description=?, short_description=?,
                  category_id=?, status=?, featured=?, sort_order=?, main_image=?, updated_by=?
                WHERE id=?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            $name,$model,$slug,$desc,$short,
            $catId,$status,$featured,$sort,$img,$userId,$id
        ]);
    }

    /** BORRAR máquina */
    public function delete(int $id): bool {
        $stmt = $this->pdo->prepare("DELETE FROM machines WHERE id=?");
        return $stmt->execute([$id]);
    }

    /** Cambiar estado rápido */
    public function toggleStatus(int $id, string $to): bool {
        if (!in_array($to, ['draft','published','archived'], true)) {
            throw new InvalidArgumentException('Estado inválido');
        }
        $stmt = $this->pdo->prepare("UPDATE machines SET status=? WHERE id=?");
        return $stmt->execute([$to, $id]);
    }

    /** Helpers */
    private function slugify(string $s): string {
        $s = strtolower(trim($s));
        $s = preg_replace('/[^a-z0-9]+/','-',$s) ?? '';
        return trim($s,'-') ?: 'item';
    }

    private function uniqueSlug(string $slug, ?int $excludeId): string {
        $base = $slug; $i = 1;
        while (true) {
            if ($excludeId) {
                $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM machines WHERE slug=? AND id<>?");
                $stmt->execute([$slug, $excludeId]);
            } else {
                $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM machines WHERE slug=?");
                $stmt->execute([$slug]);
            }
            if ((int)$stmt->fetchColumn() === 0) return $slug;
            $i++; $slug = $base.'-'.$i;
        }
    }
}
