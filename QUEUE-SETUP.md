# Queue Setup Guide for cPanel

This guide explains how to set up email queue processing on a cPanel hosting environment where you don't have SSH/terminal access.

## Problem

When emails are queued using Laravel's queue system, they need a queue worker to process them. On cPanel without SSH access, you can't run `php artisan queue:work` directly. This guide provides two solutions.

## Solution 1: Web-Accessible Queue Processor (Recommended)

This solution uses a secure HTTP endpoint that processes queue jobs, which can be called from a cPanel cron job.

### Step 1: Set Up Security Token

1. Open your `.env` file (or create it if it doesn't exist)
2. Add a secure random token:

```env
QUEUE_PROCESSOR_TOKEN=your-secure-random-token-here
```

**Important:** Generate a strong, random token. You can use:
- An online random string generator
- `openssl rand -hex 32` (if you have access)
- Any long random string (at least 32 characters recommended)

### Step 2: Set Up cPanel Cron Job

1. Log into your cPanel
2. Navigate to **Cron Jobs** (usually under "Advanced" section)
3. Add a new cron job with the following settings:

**Common Settings:**
- **Minute:** `*` (every minute)
- **Hour:** `*` (every hour)
- **Day:** `*` (every day)
- **Month:** `*` (every month)
- **Weekday:** `*` (every day of week)

**Command:**
```bash
curl -s "https://yourdomain.com/queue/process?token=YOUR_QUEUE_PROCESSOR_TOKEN"
```

Replace:
- `yourdomain.com` with your actual domain
- `YOUR_QUEUE_PROCESSOR_TOKEN` with the token you set in `.env`

**Example:**
```bash
curl -s "https://synapse-events.example.com/queue/process?token=abc123xyz789securetoken456"
```

### Step 3: Verify It's Working

1. Check your Laravel log file at `storage/logs/laravel.log`
2. Look for entries like:
   - `[QueueProcessor] Starting queue processing`
   - `[QueueProcessor] Queue processing completed`
3. You can also test the endpoint manually by visiting the URL in your browser (you should see a JSON response)

### How It Works

- The cron job calls the `/queue/process` endpoint every minute
- The endpoint processes one queue job per call
- If there are multiple jobs, they'll be processed one per minute until the queue is empty
- All activity is logged to `storage/logs/laravel.log`

## Solution 2: Use Sync Queue (Simpler, but Less Efficient)

If you prefer immediate email sending without queuing, you can switch to the "sync" queue driver. This sends emails immediately but may slow down your application if you send many emails.

### Step 1: Update Queue Configuration

In your `.env` file, change:

```env
QUEUE_CONNECTION=sync
```

### Step 2: Clear Configuration Cache

If you're using configuration caching, clear it:

```php
// Via a temporary route or artisan command
php artisan config:clear
```

### Pros and Cons

**Sync Queue:**
- ✅ Simple - no setup required
- ✅ Emails sent immediately
- ❌ Slows down page response if sending many emails
- ❌ No retry mechanism if email fails

**Database Queue with Cron:**
- ✅ Better performance - emails don't block page requests
- ✅ Automatic retry on failure
- ✅ Can process many emails efficiently
- ❌ Requires cron job setup
- ❌ Slight delay (up to 1 minute) before emails are sent

## Monitoring Queue Status

### Check Logs

Monitor your queue processing by checking `storage/logs/laravel.log`:

```bash
# View recent queue activity
tail -f storage/logs/laravel.log | grep QueueProcessor

# View notification queuing activity
tail -f storage/logs/laravel.log | grep TicketVerifiedNotification
```

### Check Database

You can check the queue status directly in your database:

**Pending Jobs:**
```sql
SELECT COUNT(*) FROM jobs WHERE reserved_at IS NULL;
```

**Failed Jobs:**
```sql
SELECT * FROM failed_jobs ORDER BY failed_at DESC LIMIT 10;
```

## Troubleshooting

### Emails Not Being Sent

1. **Check if jobs are being queued:**
   - Look for `[TicketVerifiedNotification] Notification queued` in logs
   - Check the `jobs` table in your database

2. **Check if queue processor is running:**
   - Look for `[QueueProcessor] Starting queue processing` in logs
   - Verify your cron job is set up correctly in cPanel

3. **Check for errors:**
   - Look for `[QueueProcessor] Queue processing failed` in logs
   - Check the `failed_jobs` table for failed email attempts

4. **Verify token:**
   - Ensure `QUEUE_PROCESSOR_TOKEN` is set in `.env`
   - Ensure the token in your cron job matches the one in `.env`

### Cron Job Not Running

1. Check cPanel cron job logs (usually in cPanel under "Cron Jobs" → "View Logs")
2. Test the URL manually in a browser to ensure it's accessible
3. Verify the token is correct
4. Check that your domain is accessible

### Too Many Cron Jobs

If you're processing many emails and one per minute isn't fast enough:

1. You can run the cron more frequently (e.g., every 30 seconds), but be aware:
   - cPanel may have limits on cron frequency
   - More frequent calls = more server load
   - Consider if sync queue might be better for your use case

2. Alternatively, you can modify the queue processor to process multiple jobs per call (requires code changes)

## Security Notes

- **Never commit your `.env` file** to version control
- **Keep your `QUEUE_PROCESSOR_TOKEN` secret** - treat it like a password
- The queue processor endpoint is intentionally simple - it only processes one job per call to prevent abuse
- If you suspect your token is compromised, generate a new one and update your cron job

## Additional Resources

- [Laravel Queue Documentation](https://laravel.com/docs/queues)
- [cPanel Cron Jobs Documentation](https://docs.cpanel.net/cpanel/advanced-features/cron-jobs/)

