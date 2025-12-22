# Session Tracking System

## Overview
Comprehensive session tracking module for faculty/employee to manage batch occurrences, record completion details, attach files, and document session progress. Industry-standard implementation with robust file handling.

## Components

### Database
- **session_completions**: Core session tracking (times, codes, notes, status)
- **session_attachments**: PDF, video, audio, document uploads (100MB limit per file)
- **session_notes**: Timestamped comments/follow-ups on sessions

### API Endpoints (`api/session_completion.php`)
| Action | Purpose |
|--------|---------|
| `list` | Get user's session completions |
| `get` | Fetch single session with attachments & notes |
| `create` | Create new session entry |
| `update` | Update session details |
| `complete` | Mark session as completed (times, code, notes) |
| `cancel` | Cancel a session |
| `add_note` | Add comment to session |
| `delete_note` | Remove a comment |
| `upload_attachment` | Upload PDF/video/audio/document |
| `delete_attachment` | Remove an attachment |
| `occurrences` | List pending/upcoming sessions |

### Controllers
- **SessionCompletionController**: CRUD for session tracking
- **SessionAttachmentController**: File upload/management with MIME validation

### Views & UI
- **session_tracking.php**: Full-featured dashboard with 3 tabs:
  1. **Upcoming Sessions**: Cards showing scheduled batches to complete
  2. **Completed Sessions**: Table of finished sessions with code/notes/files
  3. **History**: Timeline view of all sessions

### File Uploads
- **Location**: `public/uploads/sessions/`
- **Max Size**: 100 MB per file
- **Supported Types**:
  - PDF
  - Video (MP4, WebM, QuickTime, MPEG)
  - Audio (MP3, WAV, WebM)
  - Documents (DOCX, XLSX, DOC, XLS)
  - Images (PNG, JPG, GIF, WebP)

## Workflow

### For Faculty/Employee:
1. Click **Sessions** in nav
2. View **Upcoming Sessions** tab (shows pending batches)
3. Click **Complete** to open completion form:
   - Enter actual start/end times
   - Generate/enter unique completion code
   - Add session notes
   - Confirm
4. **Completed Sessions** tab shows finished sessions
5. Click **View** on any session to see:
   - Completion details
   - All attached files (download links)
   - Comments/notes from instructors
6. Can add notes/comments to existing sessions

### For Admin:
- Monitor session completions across faculty/staff
- Verify codes and attendance
- Access all attachments (video recordings, documents, etc.)

## Key Features

### Session Details
- Scheduled vs. Actual times (allows for delays)
- Unique completion code (for audit trail)
- Timestamped notes and author tracking

### File Management
- Safe upload with MIME validation
- Organized directory structure
- Download tracking capability
- Multiple file types per session

### Notes/Comments
- 4 note types: General, Follow-up, Issue, Achievement
- Author tracking with timestamp
- Editable/deletable comments

### Status Tracking
- Pending: Awaiting completion
- Completed: With code/notes/files
- Cancelled: Session not held

## Database Setup

**Run migrations:**
```bash
mysql -u root -p your_db < sql/migrations/20251219_create_schedule_batch_occurrences.sql
mysql -u root -p your_db < sql/migrations/20251219_alter_schedule_batches_add_weekdays.sql
mysql -u root -p your_db < sql/migrations/20251219_create_session_tracking.sql
```

**Or via phpMyAdmin:**
- Import all three `.sql` files in sequence

## Usage Examples

### Complete a Session (API)
```javascript
const formData = new FormData();
formData.append('id', sessionId);
formData.append('actual_start_time', '09:30');
formData.append('actual_end_time', '10:45');
formData.append('completion_code', 'BATCH-001-DEC19');
formData.append('notes', 'Covered Topics 1-3, Quiz scheduled for next session');

const res = await fetch('api/session_completion.php?action=complete', {
    method: 'POST',
    credentials: 'same-origin',
    body: formData
});
```

### Upload Attachment (API)
```javascript
const formData = new FormData();
formData.append('completion_id', completionId);
formData.append('file', fileInput.files[0]);
formData.append('description', 'Class recording - Session 1');

const res = await fetch('api/session_completion.php?action=upload_attachment', {
    method: 'POST',
    credentials: 'same-origin',
    body: formData
});
```

### Add Note/Comment
```javascript
const formData = new FormData();
formData.append('completion_id', completionId);
formData.append('note_text', 'Great participation today!');
formData.append('note_type', 'achievement');

await CRUD.post('api/session_completion.php?action=add_note', formData);
```

## Security & Best Practices

✅ **Implemented:**
- CSRF token validation
- Role-based access (faculty/employee only)
- Prepared statements (SQL injection prevention)
- MIME type validation for uploads
- File size limits (100 MB)
- Session-based user tracking
- Audit trail via timestamps & author IDs

✅ **Industry Standards:**
- RESTful API design
- Atomic database operations
- Transaction-safe workflows
- Proper error handling
- Organized file storage
- Metadata tracking (uploader, timestamp, type)

## Navigation
- Menu item: **Sessions** (fa-video icon)
- Accessible to: Super Admin, Branch Admin, Faculty, Employee
- URL: `/index.php?page=session_tracking`

## Future Enhancements
- Batch export (PDF/Excel)
- Session statistics dashboard
- Recurring session templates
- Email notifications on completion
- Advanced search/filtering
- Bulk file download
- Session approval workflow
