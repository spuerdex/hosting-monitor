# Multi-host Status API v1

แต่ละ Hosting VM ให้บริการ endpoint `GET /api/status` สำหรับระบบกลางอ่านข้อมูลเท่านั้น ระบบกลางต้องส่ง token ของ host นั้นผ่าน `Authorization: Bearer <token>` และต้องกำหนด timeout ต่อ host จาก registry.

Response ต้องเป็น JSON object และมี fields หลักดังนี้:

```json
{
  "schema_version": 1,
  "generated_at": "2026-09-30T12:00:00Z",
  "overall_status": "HEALTHY",
  "services": {},
  "storage": {},
  "summary": {},
  "backup": {},
  "students": []
}
```

`overall_status` ต้องเป็น `HEALTHY` หรือ `WARNING` เท่านั้นเมื่อประกาศข้อมูลสำเร็จ. `generated_at` ต้องเป็น ISO-8601 timestamp พร้อม timezone. `students` ต้องเป็น list; student identity มีขอบเขตเป็น `(host_code, student_id)`.

API นี้เป็น read-only monitoring contract: ห้ามใช้ endpoint นี้เพื่อสร้าง ลบ แก้ไข ระงับ เปิดใช้งาน เปลี่ยน quota หรือ reset credential.

Registry เก็บเพียงชื่อ environment ของ token ใน `api_token_env`; ค่าลับจริงต้องอยู่ใน environment/secret store ของ collector และห้ามอยู่ใน Git หรือ response.
