routePHP
=========

PHP 5.3 non-MVC Website Framework


How to manage scheduler with bash script and cron
=============================

<pre>
#
# Configuration
#

# Command to execute
CMD="/path/to/core/scheduler.php path_to_application brand_name daemon"
LOG="/var/log/scheduler_`date -I`.log"

# Folder to store PID files
# Script will check it and if PID is already exists in running processes just returns
# If not exists - delete PID file
# If no PID file - create new
PIDS="/var/run"

#
# END
#


# Get current PID
PID=$$

# Get script name
NAME=$(basename $0)

# Run task function
runTask() {
  # No PID file found. Create new
	echo "Create new task"
	echo -n $PID > "$PIDS/$NAME.pid"
	/usr/bin/php -f $CMD >>$LOG
	echo "Task closed"
}

# Check if file exists
if [ -f "$PIDS/$NAME.pid" ]
then
	# Found PID file
	PIDOLD=`cat "$PIDS/$NAME.pid"`
	PIDEX=`ps -p $PIDOLD -o comm=`
	if [ -z $PIDEX ]
	then
		# No such process found. Delete old PID file
		echo "Found old PID file: $PIDOLD and new PID is $PID"
		rm -f "$PIDS/$NAME.pid"
		runTask
	else
		# Found working script with stored PID. Exit
		exit
	fi
else
	runTask
fi
</pre>

<pre>
*/1 * * * * root /bin/bash /path/to/scheduler_daemon.sh >> /var/log/scheduler_cron.log 2>&1
</pre>
