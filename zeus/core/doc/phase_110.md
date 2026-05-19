# Phase 110


- Chỉnh sửa tên thành `zeus-git.sh` `zeus-git-local.sh` với REMOTE_URL của git là `git@github.com-que-nguyen:que-nguyen/zeus-dashboard.git` nhưng có ALLOWED_PATHS và chỉ có một lệnh duy nhất là `zeus-git-local.sh push` (tương đương với `git push` nhưng với remote url là `git@github.com-que-nguyen:que-nguyen/zeus-dashboard.git`)
- Tạo một `zeus-git-galaxy.sh` với REMOTE_URL của git là `git@github.com:que-galaxy/zeus-dashboard.git` với ALLOWED_PATHS:
```
ALLOWED_PATHS=(
	.gitignore
    "DEPLOY-SERVER.sh"
    "docker-compose.prod.sqlite.yml"
    "docker-compose.prod.yml"
    "docker-compose.sqlite.yml"
    "docker-compose.yml"
    "docker"
    "README.md"
    "src"
    "scripts"
)
```

gồm  `zeus-git-server.sh commit <message>`, và `zeus-git-server.sh push` (đảm bảo tự động add các file và folders nằm trong ALLOWED_PATHS)