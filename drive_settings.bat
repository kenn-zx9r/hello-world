@echo off
set STR_INPUT=Y

set /P STR_INPUT="�h���C�u���蓖�Ă��s���܂��B��낵���ł����H�iY/N�j[Y]�F "
IF "%STR_INPUT%" == "y" (
    goto YES
) ELSE IF "%STR_INPUT%"=="Y" ( 
    goto YES
) ELSE (
    goto FAILURE
)

:YES
echo �h���C�u���蓖�Ē�...
net use /delete t:
net use t: \\999.999.999.999\XXXX /user:XXX\XXXXXX XXXX
echo �h���C�u���蓖�Ă�����Ɋ������܂����B
pause
exit /B 0

:FAILURE
echo �����𒆎~���܂����B
timeout 2
exit /B 9
