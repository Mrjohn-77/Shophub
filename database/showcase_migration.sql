-- ========================================================================
-- Showcase (Shop-the-look) : ตารางฉาก + จุดคลิก จัดการจากหลังบ้านได้
-- รันใน phpMyAdmin ทั้ง localhost และ Angsila (ฐานข้อมูล s68160397)
-- ========================================================================

CREATE TABLE IF NOT EXISTS scenes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(120) NOT NULL,
  subtitle VARCHAR(120) DEFAULT NULL,
  image_url VARCHAR(2048) NOT NULL,
  sort_order INT NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS scene_hotspots (
  id INT AUTO_INCREMENT PRIMARY KEY,
  scene_id INT NOT NULL,
  x DECIMAL(5,2) NOT NULL,          -- ตำแหน่งแนวนอน เป็น % ของรูป
  y DECIMAL(5,2) NOT NULL,          -- ตำแหน่งแนวตั้ง เป็น % ของรูป
  category_id INT NOT NULL,         -- กดแล้วพาไปหมวดนี้
  sort_order INT NOT NULL DEFAULT 0,
  CONSTRAINT fk_hs_scene FOREIGN KEY (scene_id) REFERENCES scenes(id) ON DELETE CASCADE,
  CONSTRAINT fk_hs_cat   FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- seed: 4 ฉากเริ่มต้น (ภาพวาดตัวอย่าง เปลี่ยนเป็นรูปจริงได้ในหน้า admin)
INSERT INTO scenes (title, subtitle, image_url, sort_order) VALUES
('โทนอบอุ่น','Warm Minimal','assets/img/room-warm.svg',1),
('โทนเข้ม','Industrial','assets/img/room-dark.svg',2),
('โทนสว่าง','Modern Bright','assets/img/room-bright.svg',3),
('จัดโต๊ะมุมบน','Desk Flat-lay','assets/img/flatlay.svg',4);

-- seed: จุดกลางภาพ 1 จุดต่อฉาก (ชี้ไปหมวดแรกที่มี) ไว้ให้เห็นว่าทำงาน แล้วค่อยเพิ่ม/ขยับในหน้า admin
INSERT INTO scene_hotspots (scene_id, x, y, category_id, sort_order)
SELECT s.id, 50.00, 50.00, (SELECT id FROM categories ORDER BY id LIMIT 1), 0
FROM scenes s
WHERE EXISTS (SELECT 1 FROM categories);
