PROGRESS_FILE=/tmp/dependancy_iCalendar_in_progress
if [ ! -z $1 ]; then
	PROGRESS_FILE=$1
fi
if [ ! -z $2 ]; then
	PATH_JEEDOM_FILE=$2
fi
touch ${PROGRESS_FILE}
echo 0 > ${PROGRESS_FILE}
echo "********************************************************"
echo "*    'iCalendar': installation des dépendances    *"
echo "********************************************************"
echo ".......... olindoteTools class : start"
sudo php ${PATH_JEEDOM_FILE}/plugins/iCalendar/3rdparty/olindote/Tools/olindoteTools.inc.php dependancy=install plugin=iCalendar
echo "---------- olindoteTools class : end"
echo 95 > ${PROGRESS_FILE}
echo "=============== Statut ==============="
echo "***"
sudo ls -l ${PATH_JEEDOM_FILE}/data/custom/php/olindote/
echo "---------- "
echo 100 > ${PROGRESS_FILE}
echo "********************************************************"
echo "*      'iCalendar': Installation terminée         *"
echo "********************************************************"
rm ${PROGRESS_FILE}