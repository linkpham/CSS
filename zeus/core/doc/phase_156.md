# Phase 156
- Fix lỗi sau triệt để:
```
Failed to load resource: net::ERR_BLOCKED_BY_CLIENT
api/csi/summary?:1   Failed to load resource: the server responded with a status of 524 ()
api/csi/health-distribution?:1   Failed to load resource: the server responded with a status of 524 ()
api/csi/teacher-warning?:1   Failed to load resource: the server responded with a status of 524 ()
api/csi/css-performance?:1   Failed to load resource: the server responded with a status of 524 ()
api/csi/score-distribution?:1   Failed to load resource: the server responded with a status of 524 ()
csi:3186  CSI load error: SyntaxError: Failed to execute 'json' on 'Response': Unexpected token '<', "<!DOCTYPE "... is not valid JSON
    at Proxy.loadData (csi:3180:49)
    at async Proxy.init (csi:3161:13)
loadData @ csi:3186
api/csi/summary?:1   Failed to load resource: the server responded with a status of 524 ()
api/csi/health-distribution?:1   Failed to load resource: the server responded with a status of 524 ()
api/csi/css-performance?:1   Failed to load resource: the server responded with a status of 524 ()
api/csi/score-distribution?:1   Failed to load resource: the server responded with a status of 524 ()
api/csi/teacher-warning?:1   Failed to load resource: the server responded with a status of 524 ()
csi:3186  CSI load error: SyntaxError: Failed to execute 'json' on 'Response': Unexpected token '<', "<!DOCTYPE "... is not valid JSON
    at Proxy.loadData (csi:3180:49)
    at async Proxy.init (csi:3161:13)
loadData @ csi:3186
api/csi/students?&page=1&per_page=50&sort_by=health_score&sort_dir=asc:1   Failed to load resource: the server responded with a status of 524 ()
csi:3219  CSI students error: SyntaxError: Unexpected token '<', "<!DOCTYPE "... is not valid JSON
loadStudents @ csi:3219
health-distribution:1   Failed to load resource: the server responded with a status of 524 ()
score-distribution:1   Failed to load resource: the server responded with a status of 524 ()
teacher-warning:1   Failed to load resource: the server responded with a status of 524 ()
summary:1   Failed to load resource: the server responded with a status of 524 ()
css-performance:1   Failed to load resource: the server responded with a status of 524 ()
csi:3186  CSI load error: SyntaxError: Failed to execute 'json' on 'Response': Unexpected token '<', "<!DOCTYPE "... is not valid JSON
    at Proxy.loadData (csi:3180:49)
loadData @ csi:3186
students:1   Failed to load resource: the server responded with a status of 524 ()
csi:3219  CSI students error: SyntaxError: Unexpected token '<', "<!DOCTYPE "... is not valid JSON
```