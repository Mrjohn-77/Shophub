param([string]$msg = "update")

# 1) push ขึ้น GitHub
git add .
git commit -m $msg
git push

# 2) อัปขึ้น Angsila ผ่าน WinSCP (ใช้ session ชื่อ angsila ที่บันทึกไว้)
$winscp = "C:\Program Files (x86)\WinSCP\WinSCP.com"
if (-not (Test-Path $winscp)) { $winscp = "C:\Program Files\WinSCP\WinSCP.com" }
if (-not (Test-Path $winscp)) { Write-Host "ไม่พบ WinSCP.com - ตรวจว่าติดตั้ง WinSCP แล้ว"; exit 1 }

& $winscp /command `
  "open angsila" `
  "synchronize remote -filemask=|secret/;.git/;.github/;deploy.ps1 . /home/BUU/68160397/public_html/shophub" `
  "exit"

Write-Host "เสร็จแล้ว: push GitHub + อัป Angsila"
