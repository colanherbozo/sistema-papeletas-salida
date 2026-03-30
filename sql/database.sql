CREATE TABLE usuarios (
  id_usuario NUMBER PRIMARY KEY,
  nombre_completo VARCHAR2(100),
  dni VARCHAR2(15),
  correo VARCHAR2(100),
  tipo_usuario VARCHAR2(20),
  cargo VARCHAR2(100),
  estado VARCHAR2(20),
  creado_por VARCHAR2(100),
  creado_fecha TIMESTAMP DEFAULT SYSTIMESTAMP,
  modificado_por VARCHAR2(100),
  modificado_fecha TIMESTAMP
);

CREATE TABLE tipos_motivo (
  id_motivo NUMBER PRIMARY KEY,
  descripcion VARCHAR2(100),
  creado_por VARCHAR2(100),
  creado_fecha TIMESTAMP DEFAULT SYSTIMESTAMP,
  modificado_por VARCHAR2(100),
  modificado_fecha TIMESTAMP
);

CREATE TABLE papeletas_salida (
  id_papeleta NUMBER PRIMARY KEY,
  id_usuario NUMBER,
  id_motivo NUMBER,
  fecha_solicitud DATE,
  hora_salida VARCHAR2(5),
  hora_retorno VARCHAR2(5),
  estado_papeleta VARCHAR2(20),
  observaciones CLOB,
  creado_por VARCHAR2(100),
  creado_fecha TIMESTAMP DEFAULT SYSTIMESTAMP,
  modificado_por VARCHAR2(100),
  modificado_fecha TIMESTAMP,
  FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario),
  FOREIGN KEY (id_motivo) REFERENCES tipos_motivo(id_motivo)
);

CREATE TABLE historial (
  id_historial NUMBER PRIMARY KEY,
  id_papeleta NUMBER,
  id_usuario_autorizador NUMBER,
  fecha_aprobacion DATE,
  estado_final VARCHAR2(26),
  comentarios VARCHAR2(255),
  creado_por VARCHAR2(100),
  creado_fecha TIMESTAMP DEFAULT SYSTIMESTAMP,
  modificado_por VARCHAR2(100),
  modificado_fecha TIMESTAMP,
  FOREIGN KEY (id_papeleta) REFERENCES papeletas_salida(id_papeleta),
  FOREIGN KEY (id_usuario_autorizador) REFERENCES usuarios(id_usuario)
);

CREATE TABLE logs (
  id_log NUMBER PRIMARY KEY,
  id_usuario NUMBER,
  accion VARCHAR2(255),
  fecha_hora DATE,
  descripcion CLOB,
  FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario)
);

-- 3. CREAR SECUENCIAS
CREATE SEQUENCE seq_usuarios START WITH 4;
CREATE SEQUENCE seq_tipos_motivo START WITH 5;
CREATE SEQUENCE seq_papeletas_salida START WITH 4;
CREATE SEQUENCE seq_historial START WITH 1;
CREATE SEQUENCE seq_logs START WITH 1;

-- 4. INSERTAR DATOS DE PRUEBA
INSERT INTO usuarios VALUES (1, 'Luis Rojas', '12345678', 'luis.rojas@gmail.com', 'administrador', 'Coordinador Académico', 'activo', 'admin', SYSTIMESTAMP, NULL, NULL);
INSERT INTO usuarios VALUES (2, 'Sofía Medina', '23456789', 'sofia.medina@gmail.com', 'autorizador', 'Tutor Académico', 'activo', 'admin', SYSTIMESTAMP, NULL, NULL);
INSERT INTO usuarios VALUES (3, 'Michael Colan Herbozo', '75139448', 'michael@gmail.com', 'usuario', 'Estudiante de Sistemas', 'activo', 'admin', SYSTIMESTAMP, NULL, NULL);

INSERT INTO tipos_motivo VALUES (1, 'Cita médica', 'admin', SYSTIMESTAMP, NULL, NULL);
INSERT INTO tipos_motivo VALUES (2, 'Trámite estudiantil', 'admin', SYSTIMESTAMP, NULL, NULL);
INSERT INTO tipos_motivo VALUES (3, 'Asunto familiar urgente', 'admin', SYSTIMESTAMP, NULL, NULL);
INSERT INTO tipos_motivo VALUES (4, 'Otros', 'admin', SYSTIMESTAMP, NULL, NULL);

-- INTO papeletas_salida VALUES (1, 1, 1, TO_DATE('2025-06-01', 'YYYY-MM-DD'), '10:00', '12:00', 'pendiente', 'Cita médica particular', 'admin', SYSTIMESTAMP, NULL, NULL);
--INSERT INTO papeletas_salida VALUES (2, 1, 2, TO_DATE('2025-05-28', 'YYYY-MM-DD'), '15:00', '16:30', 'aprobado', 'Trámite en secretaría académica', 'admin', SYSTIMESTAMP, NULL, NULL);
--INSERT INTO papeletas_salida VALUES (3, 2, 3, TO_DATE('2025-05-20', 'YYYY-MM-DD'), '09:00', '11:00', 'rechazado', 'Emergencia con familiar', 'admin', SYSTIMESTAMP, NULL, NULL);

--INSERT INTO historial VALUES (1, 2, 2, TO_DATE('2025-05-28', 'YYYY-MM-DD'), 'aprobado', 'Autorizado por motivo válido', 'admin', SYSTIMESTAMP, NULL, NULL);
--INSERT INTO historial VALUES (2, 3, 2, TO_DATE('2025-05-20', 'YYYY-MM-DD'), 'rechazado', 'Motivo no justificado', 'admin', SYSTIMESTAMP, NULL, NULL);
DELETE FROM papeletas_salida;
COMMIT;

---TRIGGERS 
CREATE OR REPLACE TRIGGER trg_log_usuario
AFTER INSERT OR DELETE OR UPDATE ON usuarios
FOR EACH ROW
DECLARE
    v_id_usuario   NUMBER;
    v_accion       VARCHAR2(20);
    v_descripcion  VARCHAR2(4000);
BEGIN
    -- Obtener ID del usuario desde CLIENT_IDENTIFIER
    v_id_usuario := TO_NUMBER(SYS_CONTEXT('USERENV', 'CLIENT_IDENTIFIER'));

    -- Determinar la acción
    IF INSERTING THEN
        v_accion := 'INSERTAR';
        v_descripcion := 'Se insertó al usuario ' || :NEW.nombre_completo;
    ELSIF UPDATING THEN
        v_accion := 'EDITAR';
        v_descripcion := 'Se editó al usuario ' || :NEW.nombre_completo;
    ELSIF DELETING THEN
        v_accion := 'ELIMINAR';
        v_descripcion := 'Se eliminó al usuario ' || :OLD.nombre_completo;
    END IF;

    -- Insertar en la tabla logs
    INSERT INTO logs (
        id_log,
        id_usuario,
        accion,
        descripcion,
        fecha_hora
    )
    VALUES (
        seq_logs.NEXTVAL,
        v_id_usuario,
        v_accion,
        v_descripcion,
        SYSDATE
    );
END;

---2 TRIGGERS
CREATE OR REPLACE TRIGGER trg_log_papeleta
AFTER INSERT OR UPDATE OR DELETE ON papeletas_salida
FOR EACH ROW
DECLARE
    v_id_usuario  NUMBER;
    v_accion      VARCHAR2(20);
    v_descripcion CLOB;
BEGIN
    -- Obtener el ID del usuario desde el contexto
    v_id_usuario := TO_NUMBER(SYS_CONTEXT('USERENV', 'CLIENT_IDENTIFIER'));

    IF INSERTING THEN
        v_accion := 'INSERTAR';
        v_descripcion := 'Se insertó una papeleta para el usuario ID ' || :NEW.id_usuario ||
                         ' con fecha ' || TO_CHAR(:NEW.fecha_solicitud, 'YYYY-MM-DD');

    ELSIF DELETING THEN
        v_accion := 'ELIMINAR';
        v_descripcion := 'Se eliminó la papeleta ID ' || :OLD.id_papeleta ||
                         ' del usuario ID ' || :OLD.id_usuario;

    ELSIF UPDATING THEN
        IF (:OLD.estado_papeleta IS NULL AND :NEW.estado_papeleta IS NOT NULL) OR
           (:OLD.estado_papeleta IS NOT NULL AND :NEW.estado_papeleta IS NULL) OR
           (:OLD.estado_papeleta IS NOT NULL AND :NEW.estado_papeleta IS NOT NULL AND :OLD.estado_papeleta != :NEW.estado_papeleta) THEN

            IF LOWER(:NEW.estado_papeleta) = 'aprobado' THEN
                v_accion := 'APROBÓ';
                v_descripcion := 'El autorizador aprobó la papeleta ID ' || :NEW.id_papeleta;
            ELSIF LOWER(:NEW.estado_papeleta) = 'rechazado' THEN
                v_accion := 'RECHAZÓ';
                v_descripcion := 'El autorizador rechazó la papeleta ID ' || :NEW.id_papeleta;
            ELSIF LOWER(:NEW.estado_papeleta) = 'cancelado' THEN
            v_accion := 'CANCELAR';
            v_descripcion := 'Se canceló la papeleta ID ' || :NEW.id_papeleta;
            ELSE
                v_accion := 'CAMBIO DE ESTADO';
                v_descripcion := 'El autorizador cambió el estado de la papeleta ID ' || :NEW.id_papeleta ||
                             ' a "' || :NEW.estado_papeleta || '"';
            END IF;
        ELSE
            v_accion := 'ACTUALIZAR';
            v_descripcion := 'Se actualizó la papeleta ID ' || :NEW.id_papeleta;
        END IF;
    END IF;

    -- Insertar en logs
    INSERT INTO logs (
        id_log,
        id_usuario,
        accion,
        fecha_hora,
        descripcion
    ) VALUES (
        seq_logs.NEXTVAL,
        v_id_usuario,
        v_accion,
        SYSDATE,
        v_descripcion
    );
END;

--DELETE FROM historial;
--DELETE FROM logs;
--DELETE FROM papeletas_salida;

--DELETE FROM tipos_motivo;
--DELETE FROM usuarios;
create or replace PROCEDURE actualizar_papeleta (
    p_id_papeleta IN NUMBER,
    p_id_motivo IN NUMBER,
    p_fecha_solicitud IN DATE,
    p_hora_salida IN VARCHAR2,
    p_hora_retorno IN VARCHAR2,
    p_observaciones IN CLOB,
    p_modificado_por IN VARCHAR2
) AS
BEGIN
    UPDATE papeletas_salida
    SET id_motivo = p_id_motivo,
        fecha_solicitud = p_fecha_solicitud,
        hora_salida = p_hora_salida,
        hora_retorno = p_hora_retorno,
        observaciones = p_observaciones,
        modificado_por = p_modificado_por,
        modificado_fecha = SYSTIMESTAMP
    WHERE id_papeleta = p_id_papeleta;
END;

CREATE OR REPLACE PROCEDURE actualizar_papeleta (
    p_id_papeleta       IN NUMBER,
    p_id_motivo         IN NUMBER,
    p_fecha_solicitud   IN DATE,
    p_hora_salida       IN VARCHAR2,
    p_hora_retorno      IN VARCHAR2,
    p_observaciones     IN CLOB,
    p_modificado_por    IN VARCHAR2
) AS
BEGIN
    UPDATE papeletas_salida
    SET id_motivo        = p_id_motivo,
        fecha_solicitud  = p_fecha_solicitud,
        hora_salida      = p_hora_salida,
        hora_retorno     = p_hora_retorno,
        observaciones    = p_observaciones,
        modificado_por   = p_modificado_por,
        modificado_fecha = SYSTIMESTAMP
    WHERE id_papeleta = p_id_papeleta
      AND estado_papeleta = 'pendiente';
END;

create or replace PROCEDURE actualizar_usuario (
    p_id_usuario IN NUMBER,
    p_dni IN VARCHAR2,
    p_nombre_completo IN VARCHAR2,
    p_correo IN VARCHAR2,
    p_tipo_usuario IN VARCHAR2,
    p_cargo IN VARCHAR2,
    p_estado IN VARCHAR2
) AS
BEGIN
    UPDATE usuarios
    SET dni = p_dni,
        nombre_completo = p_nombre_completo,
        correo = p_correo,
        tipo_usuario = p_tipo_usuario,
        cargo = p_cargo,
        estado = p_estado
    WHERE id_usuario = p_id_usuario;
END;

create or replace PROCEDURE eliminar_papeleta (
    p_id_papeleta IN NUMBER
) AS
BEGIN
    DELETE FROM papeletas_salida
    WHERE id_papeleta = p_id_papeleta;
END;

create or replace PROCEDURE eliminar_usuario (
    p_id_usuario IN NUMBER
) AS
BEGIN
    DELETE FROM usuarios
    WHERE id_usuario = p_id_usuario;
END; 

create or replace PROCEDURE insertar_papeleta (
    p_id_usuario IN NUMBER,
    p_id_motivo IN NUMBER,
    p_fecha_solicitud IN DATE,
    p_hora_salida IN VARCHAR2,
    p_hora_retorno IN VARCHAR2,
    p_observaciones IN CLOB,
    p_creado_por IN VARCHAR2
) AS
BEGIN
    INSERT INTO papeletas_salida (
        id_papeleta, id_usuario, id_motivo, fecha_solicitud,
        hora_salida, hora_retorno, estado_papeleta, observaciones,
        creado_por, creado_fecha
    ) VALUES (
        seq_papeletas_salida.NEXTVAL, p_id_usuario, p_id_motivo, p_fecha_solicitud,
        p_hora_salida, p_hora_retorno, 'pendiente', p_observaciones,
        p_creado_por, SYSTIMESTAMP
    );
END;

create or replace PROCEDURE insertar_usuario (
    p_dni IN VARCHAR2,
    p_nombre_completo IN VARCHAR2,
    p_correo IN VARCHAR2,
    p_tipo_usuario IN VARCHAR2,
    p_cargo IN VARCHAR2,
    p_estado IN VARCHAR2
) AS
BEGIN
    INSERT INTO usuarios (
        id_usuario, dni, nombre_completo, correo,
        tipo_usuario, cargo, estado
    ) VALUES (
        seq_usuarios.NEXTVAL, p_dni, p_nombre_completo, p_correo,
        p_tipo_usuario, p_cargo, p_estado
    );
END;

create or replace PROCEDURE registrar_historial (
    p_id_papeleta IN NUMBER,
    p_id_usuario_autorizador IN NUMBER,
    p_fecha_aprobacion IN DATE,
    p_estado_final IN VARCHAR2,
    p_comentarios IN VARCHAR2
) AS
BEGIN
    INSERT INTO historial (
        id_historial, id_papeleta, id_usuario_autorizador,
        fecha_aprobacion, estado_final, comentarios
    ) VALUES (
        seq_historial.NEXTVAL, p_id_papeleta, p_id_usuario_autorizador,
        p_fecha_aprobacion, p_estado_final, p_comentarios
    );
END;

CREATE OR REPLACE PROCEDURE cancelar_papeleta (
    p_id_papeleta IN NUMBER
) AS
BEGIN
    UPDATE papeletas_salida
    SET estado_papeleta = 'Cancelado'
    WHERE id_papeleta = p_id_papeleta;

    COMMIT;
END;
--COLECCIONES
--a) INDEX BY (controlas los índices manualmente)
DECLARE

  TYPE motivo_papeleta IS TABLE OF VARCHAR2(100) INDEX BY PLS_INTEGER;

  motivos motivo_papeleta;
BEGIN
  motivos(1) := 'Consulta médica';
  motivos(2) := 'Trámite personal';
  motivos(3) := 'Emergencia familiar';

  FOR i IN 1..3 LOOP
    dbms_output.put_line('Motivo común #' || i || ': ' || motivos(i));
  END LOOP;
END;

DECLARE
  TYPE lista_usuarios IS TABLE OF VARCHAR2(100);

  nombres lista_usuarios := lista_usuarios('Juan Pérez', 'Ana Torres', 'Luis Gómez', 'Carlos Ramírez');
BEGIN
  FOR i IN 1..nombres.COUNT LOOP
    dbms_output.put_line('Usuario registrado: ' || nombres(i));
  END LOOP;
END;

DECLARE
  TYPE estados IS VARRAY(4) OF VARCHAR2(20);
  lista_estados estados := estados('pendiente', 'aprobado', 'rechazado', 'anulado');

BEGIN
  FOR i IN 1..lista_estados.COUNT LOOP
    dbms_output.put_line('Estado posible: ' || lista_estados(i));
  END LOOP;
END;
-- CURSORES
--a) Cursor Implícito
BEGIN
  FOR reg IN (
    SELECT ps.id_papeleta, u.nombre_completo, ps.estado_papeleta
    FROM papeletas_salida ps
    JOIN usuarios u ON ps.id_usuario = u.id_usuario
  ) LOOP
    dbms_output.put_line('Papeleta ID: ' || reg.id_papeleta || ' - Usuario: ' || reg.nombre_completo || ' - Estado: ' || reg.estado_papeleta);
  END LOOP;
END;
--b) Cursor Explícito (solo primeros 2 autorizadores)
DECLARE
  v_id_usuario usuarios.id_usuario%TYPE;
  v_nombre usuarios.nombre_completo%TYPE;
  v_tipo usuarios.tipo_usuario%TYPE;
  v_contador NUMBER := 0;

  CURSOR c_autorizadores IS
    SELECT id_usuario, nombre_completo, tipo_usuario
    FROM usuarios
    WHERE tipo_usuario = 'autorizador';

BEGIN
  OPEN c_autorizadores;

  LOOP
    FETCH c_autorizadores INTO v_id_usuario, v_nombre, v_tipo;
    EXIT WHEN c_autorizadores%NOTFOUND;

    v_contador := v_contador + 1;
    dbms_output.put_line('Autorizador ' || v_contador || ': ' || v_nombre || ' (ID: ' || v_id_usuario || ')');

    IF v_contador = 2 THEN
      EXIT;
    END IF;
  END LOOP;

  CLOSE c_autorizadores;
END;
