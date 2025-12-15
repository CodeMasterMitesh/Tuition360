<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Class Schedule</title>
    <style>
        body { font-family: Arial, sans-serif; color: #333; }
        .card { border:1px solid #e5e7eb; border-radius:8px; padding:16px; background:#fafafa; }
        .title { font-size:18px; margin:0 0 8px; }
        .muted { color:#6b7280; font-size:12px; }
        .pill { display:inline-block; padding:4px 8px; background:#eef2ff; color:#4338ca; border-radius:12px; font-size:12px; margin-right:6px; }
        .section { margin-top:12px; }
    </style>
</head>
<body>
    <div class="card">
        <p class="muted">Hello {{student_name}},</p>
        <h2 class="title">Your class schedule is ready for <strong>{{batch_title}}</strong></h2>

        <div class="section">
            <div><strong>Faculty:</strong> {{faculty_name}}</div>
            <div><strong>Recurrence:</strong> {{recurrence}}</div>
            <div><strong>Schedule:</strong> {{timing}}</div>
        </div>

        <div class="section">
            <strong>Subjects:</strong>
            <div>{{subjects}}</div>
        </div>

        <div class="section">
            <strong>Notes:</strong>
            <div>{{notes}}</div>
        </div>

        <p class="muted">If you have questions, please contact your branch admin.</p>
    </div>
</body>
</html>
