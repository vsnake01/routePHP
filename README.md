routePHP
=========

PHP 5.3 non-MVC Website Framework


How to manage queue with god
=============================

<pre>
time = Time.now

God.watch do |w|
  w.name = "routePHP"
  w.start = "/usr/bin/php -f /path/to/project/core/scheduler.php"
  w.stop = "/bin/kill `ps -ef | grep scheduler.php | grep -v grep | awk '{print $2}'`"
  w.log = "/var/log/scheduler_" + time.strftime("%Y-%m-%d") + ".log"
  w.keepalive(:cpu_max => 50.percent)
end

</pre>
