--
-- PostgreSQL database dump
--

-- Dumped from database version 9.5.11
-- Dumped by pg_dump version 9.5.11

SET statement_timeout = 0;
SET lock_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;
SET check_function_bodies = false;
SET client_min_messages = warning;
SET row_security = off;

--
-- Name: plpgsql; Type: EXTENSION; Schema: -; Owner: 
--

CREATE EXTENSION IF NOT EXISTS plpgsql WITH SCHEMA pg_catalog;


--
-- Name: EXTENSION plpgsql; Type: COMMENT; Schema: -; Owner: 
--

COMMENT ON EXTENSION plpgsql IS 'PL/pgSQL procedural language';


SET search_path = public, pg_catalog;

SET default_tablespace = '';

SET default_with_oids = false;

--
-- Name: consolidado_chaea_chaea_junior; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE consolidado_chaea_chaea_junior (
    id integer NOT NULL,
    documento text,
    fecha date,
    hora text,
    test text,
    activo real,
    reflexivo real,
    teorico real,
    pragmatico real
);


ALTER TABLE consolidado_chaea_chaea_junior OWNER TO postgres;

--
-- Name: consolidado_chaea_chaea_junior_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE consolidado_chaea_chaea_junior_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE consolidado_chaea_chaea_junior_id_seq OWNER TO postgres;

--
-- Name: consolidado_chaea_chaea_junior_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE consolidado_chaea_chaea_junior_id_seq OWNED BY consolidado_chaea_chaea_junior.id;


--
-- Name: consolidado_felder_vark; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE consolidado_felder_vark (
    id integer NOT NULL,
    documento text,
    fecha date,
    hora text,
    test text,
    activo text,
    sensorial text,
    visual text,
    secuencial text,
    reflexivo text,
    intuitivo text,
    verbal text,
    global text,
    auditivo text,
    lector_escritor text,
    kinestesico text
);


ALTER TABLE consolidado_felder_vark OWNER TO postgres;

--
-- Name: consolidado_felder_vark_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE consolidado_felder_vark_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE consolidado_felder_vark_id_seq OWNER TO postgres;

--
-- Name: consolidado_felder_vark_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE consolidado_felder_vark_id_seq OWNED BY consolidado_felder_vark.id;


--
-- Name: cuestionario; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE cuestionario (
    id integer NOT NULL,
    documento text,
    respuesta1 text,
    respuesta2 text,
    respuesta3 text,
    fecha date,
    hora text
);


ALTER TABLE cuestionario OWNER TO postgres;

--
-- Name: cuestionario_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE cuestionario_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE cuestionario_id_seq OWNER TO postgres;

--
-- Name: cuestionario_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE cuestionario_id_seq OWNED BY cuestionario.id;


--
-- Name: login; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE login (
    cedula text,
    email text,
    fecha date,
    id integer NOT NULL,
    hora text
);


ALTER TABLE login OWNER TO postgres;

--
-- Name: login_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE login_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE login_id_seq OWNER TO postgres;

--
-- Name: login_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE login_id_seq OWNED BY login.id;


--
-- Name: question_security; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE question_security (
    id integer NOT NULL,
    pregunta text
);


ALTER TABLE question_security OWNER TO postgres;

--
-- Name: pregunta_seguridad_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE pregunta_seguridad_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE pregunta_seguridad_id_seq OWNER TO postgres;

--
-- Name: pregunta_seguridad_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE pregunta_seguridad_id_seq OWNED BY question_security.id;


--
-- Name: programa_curricular; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE programa_curricular (
    codigo_carrera integer NOT NULL,
    nombre_carrera text
);


ALTER TABLE programa_curricular OWNER TO postgres;

--
-- Name: programa_curricular_codigo_carrera_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE programa_curricular_codigo_carrera_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE programa_curricular_codigo_carrera_seq OWNER TO postgres;

--
-- Name: programa_curricular_codigo_carrera_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE programa_curricular_codigo_carrera_seq OWNED BY programa_curricular.codigo_carrera;


--
-- Name: usuario; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE usuario (
    cedula text NOT NULL,
    nombre_style text,
    fecha_nacimiento date,
    usuario text,
    password text,
    correo text,
    fecha date,
    hora text,
    genero text,
    carrera integer,
    pregunta integer,
    respuesta text,
    token text,
    admin text
);


ALTER TABLE usuario OWNER TO postgres;

--
-- Name: id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY consolidado_chaea_chaea_junior ALTER COLUMN id SET DEFAULT nextval('consolidado_chaea_chaea_junior_id_seq'::regclass);


--
-- Name: id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY consolidado_felder_vark ALTER COLUMN id SET DEFAULT nextval('consolidado_felder_vark_id_seq'::regclass);


--
-- Name: id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY cuestionario ALTER COLUMN id SET DEFAULT nextval('cuestionario_id_seq'::regclass);


--
-- Name: id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY login ALTER COLUMN id SET DEFAULT nextval('login_id_seq'::regclass);


--
-- Name: codigo_carrera; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY programa_curricular ALTER COLUMN codigo_carrera SET DEFAULT nextval('programa_curricular_codigo_carrera_seq'::regclass);


--
-- Name: id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY question_security ALTER COLUMN id SET DEFAULT nextval('pregunta_seguridad_id_seq'::regclass);


--
-- Data for Name: consolidado_chaea_chaea_junior; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY consolidado_chaea_chaea_junior (id, documento, fecha, hora, test, activo, reflexivo, teorico, pragmatico) FROM stdin;
109	1002609260	2020-08-26	15:35:48	Chaea	80	70	45	55
110	1002566850	2020-08-26	15:57:29	Chaea	90	70	75	65
111	1006998171	2020-08-26	16:39:11	Chaea	50	75	25	65
69	1060879126	2020-08-25	13:45:33	Chaea	60	85	55	70
70	1053811412	2020-08-25	14:58:12	Chaea	35	85	55	50
71	1060879126	2020-08-25	18:26:26	Chaea Junior	90.9091034	90.9091034	90.9091034	90.9091034
72	1053867032	2020-08-26	09:01:17	Chaea	65	75	45	55
73	1007233006	2020-08-26	09:01:47	Chaea	65	90	80	70
74	1000252827	2020-08-26	09:02:38	Chaea	85	65	45	75
75	1053790466	2020-08-26	09:03:23	Chaea	60	75	70	65
76	1002634682	2020-08-26	09:04:08	Chaea	45	90	95	75
77	1193557800	2020-08-26	09:05:03	Chaea	40	90	60	55
78	1060655826	2020-08-26	09:05:13	Chaea	75	60	40	70
79	1002670044	2020-08-26	09:05:24	Chaea	70	75	60	65
80	1002609423	2020-08-26	09:06:29	Chaea	35	85	65	50
81	1053852205	2020-08-26	09:08:29	Chaea	70	85	35	45
82	1005206813	2020-08-26	09:10:14	Chaea	70	85	50	55
83	1007568947	2020-08-26	09:11:15	Chaea	40	70	60	70
84	1053871974	2020-08-26	09:13:09	Chaea	80	80	75	85
85	1002654745	2020-08-26	09:14:27	Chaea	30	80	60	65
86	1053855736	2020-08-26	09:14:50	Chaea	50	45	15	55
87	1006814723	2020-08-26	09:16:49	Chaea	90	80	40	60
88	1002656028	2020-08-26	09:17:17	Chaea	70	95	80	85
89	1002655121	2020-08-26	09:18:35	Chaea	20	85	55	75
90	1126602847	2020-08-26	09:19:41	Chaea	55	95	70	60
91	1053862979	2020-08-26	09:29:16	Chaea	60	60	55	75
92	1053862979	2020-08-26	09:35:33	Chaea Junior	27.2726994	72.7273026	36.3636017	72.7273026
93	1053860219	2020-08-26	09:35:52	Chaea	55	85	90	60
94	1005474542	2020-08-26	09:38:43	Chaea	55	85	75	85
95	1003334355	2020-08-26	09:39:05	Chaea	55	80	60	50
96	1053869341	2020-08-26	10:11:25	Chaea	80	55	65	90
98	1053863442	2020-08-26	11:06:39	Chaea	35	75	50	60
100	1067958178	2020-08-26	11:24:12	Chaea	50	65	55	65
104	1005701620	2020-08-26	12:54:08	Chaea	40	100	85	55
105	1053840867	2020-08-26	14:14:03	Chaea	45	90	75	45
106	1007233204	2020-08-26	14:16:44	Chaea	40	80	70	50
108	1002634921	2020-08-26	14:33:40	Chaea	60	75	60	75
112	1088599326	2020-08-26	17:03:23	Chaea	45	45	45	45
114	1004508273	2020-08-26	17:37:04	Chaea	50	75	80	55
115	1006999331	2020-08-26	18:31:34	Chaea	35	85	55	65
116	1002591004	2020-08-26	19:33:16	Chaea	65	80	80	70
117	1053825726	2020-08-26	20:07:57	Chaea	55	90	85	65
118	1003689652	2020-08-26	21:15:21	Chaea	25	90	70	70
119	1002581139	2020-08-26	21:25:25	Chaea	40	75	60	65
120	1053825726	2020-08-26	21:34:04	Chaea	40	75	85	85
121	1193589552	2020-08-26	22:39:58	Chaea	35	85	85	75
122	1007628722	2020-08-27	07:37:09	Chaea	55	85	60	75
123	1002567315	2020-08-27	07:42:12	Chaea	55	80	85	75
124	1053844329	2020-08-30	18:27:38	Chaea	40	95	95	80
127	1053869586	2020-11-08	16:40:17	Chaea	45	75	50	50
128	1053873232	2020-11-09	10:41:15	Chaea Junior	45.4544983	90.9091034	90.9091034	63.6363983
129	1193589331	2020-11-09	10:53:00	Chaea	50	85	70	70
130	1053817676	2020-11-09	15:23:15	Chaea	70	60	70	80
131	1110599607	2020-11-09	20:34:15	Chaea	65	55	50	55
132	1053841556	2020-11-09	21:44:10	Chaea	60	70	85	85
133	1010132887	2020-11-10	12:13:20	Chaea	50	80	90	80
134	1053871829	2020-11-10	20:35:14	Chaea	70	70	70	80
135	1113686690	2020-11-13	15:07:15	Chaea	60	80	50	65
137	1053837753	2020-12-09	15:59:13	Chaea	50	55	65	40
138	1053870754	2020-12-22	10:20:04	Chaea	55	75	75	70
45	1002592567	2021-02-25	09:03:11	Chaea	30	90	85	70
46	1002654030	2021-02-25	09:03:59	Chaea	25	90	45	45
47	1002546018	2021-02-25	09:05:40	Chaea	45	65	35	55
48	1002799561	2021-02-25	09:06:54	Chaea	75	90	75	55
49	1024460126	2021-02-25	09:11:34	Chaea	60	100	60	70
51	1002755036	2021-02-25	09:46:51	Chaea	50	80	45	35
52	1000806344	2021-02-25	09:56:16	Chaea	55	90	65	80
53	1055750069	2021-02-25	10:02:24	Chaea	90	90	80	95
136	1053828905	2021-03-10	00:25:41	Chaea Junior	54.5454559	45.4545441	72.727272	63.636364
55	1056120203	2021-02-25	11:14:27	Chaea	80	50	55	50
58	10002547048	2021-02-25	14:26:50	Chaea	80	60	60	75
59	1053765252	2021-02-25	15:58:29	Chaea	75	80	75	70
60	1002542442	2021-02-25	17:21:31	Chaea	65	95	75	65
61	1114952117	2021-02-25	18:49:31	Chaea	55	90	85	70
62	1002654063	2021-02-25	20:06:54	Chaea	55	85	80	90
63	1055750581	2021-02-25	20:07:12	Chaea	50	90	100	90
64	1003568721	2021-02-25	20:19:36	Chaea	50	75	80	50
142	1053828905	2021-03-10	00:34:53	Chaea Junior	100	100	100	100
66	1002566491	2021-02-26	09:11:29	Chaea	60	90	65	60
144	1129292022	2021-03-10	14:59:28	Chaea	45	50	45	40
68	123456789	2021-02-26	15:32:48	Chaea	100	100	100	100
146	1129292022	2021-03-10	15:13:23	Chaea Junior	45.4545441	54.5454559	63.636364	45.4545441
148	1053870754	2021-03-11	20:34:20	Chaea Junior	90.9090881	90.9090881	54.5454559	72.727272
150	1007513044	2021-03-23	17:14:27	Chaea	45	80	50	55
152	1053865271	2021-03-23	19:16:37	Chaea	40	90	80	85
154	1004728747	2021-03-23	20:29:49	Chaea	55	85	65	70
156	1002633963	2021-03-24	12:21:17	Chaea	50	60	75	85
158	1004561711	2021-04-01	18:08:53	Chaea	35	90	60	70
160	1024460126	2021-04-03	11:33:30	Chaea	65	85	65	65
162	1193568820	2021-04-05	18:27:39	Chaea	70	100	95	100
164	1054858688	2021-04-05	21:15:32	Chaea	65	55	55	70
166	1007233436	2021-04-17	22:23:05	Chaea	40	75	70	60
168	1007232295	2021-04-27	10:03:32	Chaea	55	80	75	75
143	1053828905	2021-03-10	00:36:39	Chaea	100	100	100	100
145	1129292022	2021-03-10	15:11:18	Chaea Junior	36.363636	54.5454559	54.5454559	45.4545441
147	1053870754	2021-03-11	20:32:27	Chaea	60	50	60	60
151	1060653961	2021-03-23	17:52:12	Chaea	50	90	80	50
153	1053865271	2021-03-23	19:41:04	Chaea Junior	36.363636	100	72.727272	90.9090881
155	1002654903	2021-03-23	23:03:18	Chaea	55	85	65	35
157	1077876513	2021-03-24	15:32:57	Chaea	80	75	75	70
159	1002654030	2021-04-03	11:29:30	Chaea	25	95	55	35
161	1002566183	2021-04-05	13:02:13	Chaea	60	80	60	70
163	1053857294	2021-04-05	20:17:36	Chaea	65	70	55	55
97	1007233298	2021-03-01	22:33:56	Chaea	70	85	80	75
165	1129292022	2021-04-07	15:27:48	Chaea	35	60	75	40
99	1053872257	2021-03-01	22:49:41	Chaea	65	95	60	70
167	1060270184	2021-04-26	14:46:11	Chaea	40	90	70	60
101	1192904131	2021-03-02	02:06:10	Chaea	55	90	80	70
169	1007232295	2021-04-27	10:43:26	Chaea Junior	63.636364	81.8181839	63.636364	54.5454559
103	1053860467	2021-03-02	02:35:46	Chaea	60	90	85	70
171	1053835141	2021-04-27	16:07:24	Chaea	80	95	100	80
172	1053872060	2021-08-17	17:08:39	Chaea	60	80	65	65
107	1193209085	2021-03-02	08:05:24	Chaea	60	80	65	70
173	1007233130	2021-11-21	10:12:00	Chaea	45	80	70	55
174	1007445150	2021-11-21	13:51:08	Chaea	45	85	90	55
175	1002817769	2021-11-21	14:38:45	Chaea	25	100	70	80
176	1054992077	2021-11-21	18:08:36	Chaea	10	85	95	65
177	1002634007	2021-11-21	20:42:19	Chaea	80	70	80	75
113	1015431061	2021-03-02	14:45:35	Chaea	30	60	70	75
178	1002938965	2021-11-21	22:02:58	Chaea	65	100	75	75
179	100681607	2021-11-21	23:14:30	Chaea	60	65	55	65
180	1010156654	2021-11-21	23:39:52	Chaea	60	95	75	75
181	1010156654	2021-11-21	23:44:23	Chaea Junior	36.363636	100	81.8181839	54.5454559
183	1002591341	2021-11-22	16:36:55	Chaea	40	90	80	65
184	1002944640	2021-11-23	21:57:03	Chaea	55	90	85	55
185	1060597339	2021-11-23	22:05:25	Chaea	50	85	65	70
186	1053873179	2021-11-24	00:39:24	Chaea	80	55	55	60
187	1002637036	2021-11-24	08:45:24	Chaea	60	90	65	50
188	1002566318	2021-11-24	11:27:23	Chaea	55	80	60	50
125	1053828905	2021-03-09	23:56:23	Chaea Junior	27.272728	54.5454559	81.8181839	45.4545441
126	1053828905	2021-03-09	23:57:40	Chaea	65	55	55	55
189	1053873179	2021-11-24	13:25:12	Chaea	85	55	70	75
190	1004561711	2021-12-13	18:08:01	Chaea	50	80	65	70
191	1006121493	2022-03-10	07:52:49	Chaea	80	90	60	85
192	1056120807	2022-03-10	07:53:46	Chaea	70	85	80	80
193	1054857016	2022-03-10	07:54:08	Chaea	50	70	70	75
194	1004831441	2022-03-10	07:54:47	Chaea	50	75	80	75
195	1056120654	2022-03-10	07:55:03	Chaea	65	90	80	75
196	1002633039	2022-03-10	07:55:18	Chaea	55	90	70	55
197	1032393084	2022-03-10	07:55:39	Chaea	50	90	85	90
198	1055358298	2022-03-10	07:55:48	Chaea	40	85	80	65
199	1055752118	2022-03-10	07:55:53	Chaea	65	70	65	55
200	1006881524	2022-03-10	07:56:31	Chaea	65	80	65	70
201	1232388951	2022-03-10	07:56:33	Chaea	40	85	60	75
202	1107058708	2022-03-10	07:56:36	Chaea	45	90	65	55
203	1055751648	2022-03-10	07:56:46	Chaea	40	100	75	60
204	1055752179	2022-03-10	07:57:05	Chaea	50	75	65	65
205	1004567751	2022-03-10	07:57:38	Chaea	55	65	55	65
206	1002634179	2022-03-10	07:57:45	Chaea	80	90	70	75
207	1056121172	2022-03-10	07:58:02	Chaea	40	80	65	55
208	1055750988	2022-03-10	07:58:20	Chaea	35	70	75	70
209	1055751716	2022-03-10	07:58:46	Chaea	55	65	50	30
210	1053869969	2022-03-10	07:59:38	Chaea	70	85	50	75
211	1094889977	2022-03-10	07:59:54	Chaea	55	85	75	75
212	1086298059	2022-03-10	08:00:53	Chaea	70	55	50	50
213	1192800056	2022-03-10	08:01:37	Chaea	60	75	55	75
214	1000130209	2022-03-10	19:03:55	Chaea	35	85	50	55
215	1000654412	2022-05-16	21:21:58	Chaea	65	65	75	80
216	1004699368	2022-05-16	21:25:03	Chaea	60	80	55	50
217	1004776699	2022-05-16	21:45:50	Chaea	60	25	30	60
218	1004752502	2022-05-17	21:08:34	Chaea	60	65	80	80
\.


--
-- Name: consolidado_chaea_chaea_junior_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('consolidado_chaea_chaea_junior_id_seq', 219, true);


--
-- Data for Name: consolidado_felder_vark; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY consolidado_felder_vark (id, documento, fecha, hora, test, activo, sensorial, visual, secuencial, reflexivo, intuitivo, verbal, global, auditivo, lector_escritor, kinestesico) FROM stdin;
177	1003568721	2021-02-25	17:38:54	Felder-Vark	\N	\N	0	5	\N	\N	\N	6	2	1	0
178	1129292022	2021-02-26	16:17:42	Felder	\N	\N	1A	3A	1B	5B	\N	\N	\N	\N	\N
179	1129292022	2021-03-01	15:12:12	Felder-Vark	\N	\N	0	6	\N	\N	\N	5	1	1	2
180	1129292022	2021-03-01	15:20:50	Felder-Vark	\N	\N	1	5	\N	\N	\N	5	0	2	0
181	1053828905	2021-03-09	23:58:27	Felder	1A	1A	\N	\N	\N	\N	1B	3B	\N	\N	\N
182	1053828905	2021-03-10	00:38:39	Felder	11A	11A	11A	11A	\N	\N	\N	\N	\N	\N	\N
183	1053828905	2021-03-10	00:39:18	Vark	\N	\N	1	\N	\N	\N	\N	\N	5	3	7
184	1053828905	2021-03-10	00:40:43	Felder-Vark	\N	\N	1	3	\N	\N	\N	8	3	1	1
185	1129292022	2021-03-10	15:37:15	Vark	\N	\N	8	\N	\N	\N	\N	\N	3	3	2
186	1129292022	2021-03-10	15:51:41	Felder	3A	\N	\N	3A	\N	1B	1B	\N	\N	\N	\N
187	1053870754	2021-03-11	20:49:03	Felder	3A	3A	5A	3A	\N	\N	\N	\N	\N	\N	\N
188	1053870754	2021-03-11	20:50:20	Vark	\N	\N	1	\N	\N	\N	\N	\N	5	3	7
189	1053870754	2021-03-11	20:51:24	Felder-Vark	\N	\N	1	1	\N	\N	\N	0	1	0	1
190	1053870754	2021-03-11	20:51:24	Felder-Vark	\N	\N	1	1	\N	\N	\N	0	1	0	1
191	1053828905	2021-03-22	22:47:04	Felder-Vark	\N	\N	1	0	\N	\N	\N	0	1	0	0
192	1053865271	2021-03-23	19:30:48	Felder	1A	\N	3A	\N	\N	3B	\N	5B	\N	\N	\N
193	1053865271	2021-03-23	19:50:03	Vark	\N	\N	6	\N	\N	\N	\N	\N	2	3	5
194	1053865271	2021-03-23	20:02:44	Felder-Vark	\N	\N	2	4	\N	\N	\N	7	0	1	0
195	1053865271	2021-03-23	20:18:52	Vark	\N	\N	1	\N	\N	\N	\N	\N	5	3	7
196	1053865271	2021-03-23	20:33:47	Vark	\N	\N	7	\N	\N	\N	\N	\N	2	3	4
197	1007232295	2021-04-27	10:20:49	Felder	5A	7A	5A	\N	\N	\N	\N	1B	\N	\N	\N
198	1007232295	2021-04-27	10:30:20	Vark	\N	\N	6	\N	\N	\N	\N	\N	2	4	4
199	1007232295	2021-04-27	11:05:13	Felder-Vark	\N	\N	2	8	\N	\N	\N	3	1	1	3
200	1010156654	2021-11-21	23:53:03	Felder	\N	9A	7A	3A	3B	\N	\N	\N	\N	\N	\N
201	1010156654	2021-11-21	23:57:43	Vark	\N	\N	4	\N	\N	\N	\N	\N	2	3	7
202	1010156654	2021-11-22	00:01:57	Felder-Vark	\N	\N	1	7	\N	\N	\N	4	0	0	3
\.


--
-- Name: consolidado_felder_vark_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('consolidado_felder_vark_id_seq', 202, true);


--
-- Data for Name: cuestionario; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY cuestionario (id, documento, respuesta1, respuesta2, respuesta3, fecha, hora) FROM stdin;
19	1129292022	mapa conceptual\r\nmapas mentales\r\ncuadros comparativos\r\n	Reflexión crítica sobre artículo o texto breve encontrado.	Resumen Analítico.	2021-03-01	15:19:54
20	1053828905	dfdfgdfg	Cuadro Sinóptico.	Cuadro Sinóptico, Construir una presentación sobre la temática que sintetice lo más importante.	2021-03-10	00:39:53
21	1053870754	pruebaprueba	Cuadro Sinóptico, Resumen Analítico, Exponer detalladamente una aplicación simple sobre conceptos o temática .	Resumen Analítico, Construir una presentación sobre la temática que sintetice lo más importante, Mapa Mental o Conceptual .	2021-03-11	20:52:11
22	1129292022				2021-04-05	18:06:13
24	1007232295	pues primero uso el metodo de organizar que debo hacer. luego ya lo aplico , en si es poner una lista de cada cosa que debo hacer en la tarea para asi realizarla, mas con esta virtualidad es mas dificil seguirlo porque hay muchas distracciones o preocupaciones por diferentes tipos de tareas academicas.	Cuadro Comparativo, Exponer detalladamente una aplicación simple sobre conceptos o temática , Construir una presentación sobre la temática que sintetice lo más importante.	Cuadro Sinóptico, Reflexión crítica sobre artículo o texto breve encontrado, Resumen Analítico,  Aplicación práctica (Desarrollo) de la temática, Mapa Mental o Conceptual .	2021-04-27	10:07:50
25	1010156654	Hacer una lista de actividades para todos los días de la semana	Exponer detalladamente una aplicación simple sobre conceptos o temática , Construir una presentación sobre la temática que sintetice lo más importante,  Aplicación práctica (Desarrollo) de la temática, Mapa Mental o Conceptual .	Reflexión crítica sobre artículo o texto breve encontrado, Resumen Analítico.	2021-11-21	23:46:08
26	1192800056	Procesar la información leyendo y tomando apuntes de cosas relevantes.	Resumen Analítico.	Cuadro Sinóptico.	2022-03-10	07:52:14
\.


--
-- Name: cuestionario_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('cuestionario_id_seq', 26, true);


--
-- Data for Name: login; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY login (cedula, email, fecha, id, hora) FROM stdin;
1053828905	joamontesgi@unal.edu.co	2022-07-07	783	15:28:44
963852741	ndduqueme@unal.edu.co	2022-07-07	784	15:30:19
963852741	ndduqueme@unal.edu.co	2022-07-07	785	15:31:01
963852741	ndduqueme@unal.edu.co	2022-07-07	786	17:23:26
1053782025	vtabaresm@unal.edu.co	2022-07-11	787	10:39:49
1053855736	ansanchezmo@unal.edu.co	2022-07-25	788	11:01:23
1053828905	joamontesgi@unal.edu.co	2022-07-25	789	11:14:09
1053828905	joamontesgi@unal.edu.co	2022-07-25	790	11:20:00
1053828905	joamontesgi@unal.edu.co	2022-08-11	791	07:11:15
1053828905	joamontesgi@unal.edu.co	2022-09-06	792	09:58:15
1053828905	joamontesgi@unal.edu.co	2022-09-08	793	08:20:03
\.


--
-- Name: login_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('login_id_seq', 793, true);


--
-- Name: pregunta_seguridad_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('pregunta_seguridad_id_seq', 1, false);


--
-- Data for Name: programa_curricular; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY programa_curricular (codigo_carrera, nombre_carrera) FROM stdin;
1	'Administración de Empresas'
2	'Administración de Sistemas Informáticos'
3	'Arquitectura'
4	'Gestión Cultural y Comunicativa'
5	'Ingeniería Civil'
6	'Ingeniería Eléctrica'
7	'Ingeniería Electrónica'
8	'Ingeniería Física'
9	'Ingeniería Industrial'
10	'Ingeniería Química'
11	'Matemáticas'
12	'Otra'
\.


--
-- Name: programa_curricular_codigo_carrera_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('programa_curricular_codigo_carrera_seq', 12, true);


--
-- Data for Name: question_security; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY question_security (id, pregunta) FROM stdin;
1	'Mejor amigo de la infancia'
2	'Nombre de tu primera mascota'
3	'Ciudad de origen de tu artista favorito'
\.


--
-- Data for Name: usuario; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY usuario (cedula, nombre_style, fecha_nacimiento, usuario, password, correo, fecha, hora, genero, carrera, pregunta, respuesta, token, admin) FROM stdin;
1001192517	Alvaro Alvino Camacho Bona	2002-03-21	Alvaro Camacho	$2y$10$MLEP1B5UwL4Uniqlwb9HIeb5HoUzkaBnIzhHaGSUUFt/IJ6sXp53q	alcamachob@unal.edu.co	2020-08-26	08:51:33	Masculino	2	1	Welshel	\N	No
1002577143	Julián Andrés Cubides Palacio	2001-07-23	jcubidesp	$2y$10$GArpYpFTZzufxJ6Vq/CFS.n0jjlrW.vBC8BgOkA/JLIp/tcfsSxwC	jcubidesp@unal.edu.co	2020-08-26	08:47:58	Masculino	2	1	Alejandro	\N	No
1002609260	Juan Sebastian	2002-10-19	chamu	$2y$10$sy0SrHAhRqaIUy4pD83dn.w2w/Pd06t1mfTxkoZV04m8PK0/uC0KS	jchamucero@unal.edu.co	2020-08-26	08:48:33	Masculino	2	2	lulu	\N	No
1002609423	Juan José Bañol	2002-02-15	jbanol	$2y$10$TXn9Og1g2qfjcWK1q8CcReyBEK2uTdSSRTB8AkC/akCme2/df3/wi	jbanol@unal.edu.co	2020-08-26	08:40:30	Masculino	2	1	Nadie	\N	No
1002634682	jeronimo sanchez holguin	2002-08-02	jsanchezho	$2y$10$OWPcUsK/ZAS4tZERJTLuOeDGh/NGMZKtcEPP4Vc0gc7Rht/7ch0y2	jsanchezho@unal.edu.co	2020-08-26	08:48:09	Masculino	2	2	toto	\N	No
1002655121	Luis Felipe Tabares Valencia	2002-02-09	ltabaresv	$2y$10$BeAJP/5MNj/gsKUSJMHXxe6/q1plV1.9hjl41YmNwu4u2ZAuPisE2	ltabaresv@unal.edu.co	2020-08-26	08:46:00	Masculino	2	3	Reikiavik	\N	No
1002656028	pablo esteban marin mejia	2001-08-23	pamarinm	$2y$10$8/7/Nrvdb9lJuRVirrRo2eN/E8PSbNSuaqPkyahP/R2A8sczrOWN2	pamarinm@unal.edu.co	2020-08-26	08:46:32	Masculino	2	1	dai	\N	No
1003334355	Jefrey Esteban Henao Ojeda	2002-09-19	Jehenaoo	$2y$10$x89FIor66Lw8hpLC5btAvuuXDQAyaHLzDYNX/PUZ8PgWQ.435Llfm	jehenaoo@unal.edu.co	2020-08-26	09:15:39	Masculino	2	2	Tom	\N	No
1003825082	Jahir camilo velasquez bustos	2002-10-28	Camilo Velasquez	$2y$10$ltUDJHXZwREu7PfRX3vKcuM0APIl527C6.g2PGjnpMQWN4Y1oR.yG	Jvelasquezbu@unal.edu.co	2020-08-26	08:46:24	Masculino	2	1	Carlos	\N	No
1005474542	Rafael Guillermo De Avila Banquez	2003-05-19	Rafael De Avila	$2y$10$XwI1RO5bwltm4pIDBsUodudkwfA/kktloUv8TKZnNGKIGDaAkXX.S	rdea@unal.edu.co	2020-08-26	09:14:56	Masculino	2	3	Puerto Rico	\N	No
1005206813	Hernan Rios Iglesias	2000-03-17	hriosi	$2y$10$2YUgG/HbagX01LiFYREvLO0c8TEBTE5bjKx6zMwfiSg4uAPCoXF1K	hriosi@unal.edu.co	2020-08-26	08:47:17	Masculino	2	2	niño	\N	No
1006506291	Saira Salazar Nuñez	2002-02-24	ssalazarn	$2y$10$ZSQJpiENovbVXuSVlWQGwegnxuUNYhNTojFOkW9i9pfRPFNxlQXm.	ssalazarn@unal.edu.co	2020-08-26	10:08:53	Femenino	2	3	cali	\N	No
1006814723	Sara Isabel Ospina Valderrama	2000-03-21	Saospinav	$2y$10$sy..x81bX9PG9x/dNVzXG.IBtKqJaW6HcolE.pYMp.zyccSmQdO9O	saospinav@unal.edu.co	2020-08-26	08:49:16	Femenino	2	2	Canchilas	\N	No
1007233204	Andrés Mauricio Valencia Zuluaga	2000-05-29	avalenciazu	$2y$10$OJW7hjwi8IKQGGLvc7f2j.1K4PqnP9O2gcm85g54/Ot.nMthr9.gW	avalenciazu@unal.edu.co	2020-08-26	08:48:24	Masculino	2	2	Toby	\N	No
1053811412	Alejandra Sánchez Morales	1991-06-20	Alejandra20	$2y$10$wxkBG6BSacGM3xVQ9hgnDONL.9Q05dnVMtQhZ0dubwU0nqss05Je6	alejandrasanchezmo06@gmail.com	2020-08-25	14:40:33	Femenino	2	2	mechitas	\N	No
1053852205	Juan Sebastian Palacio Giraldo	1996-08-17	jpalacio	$2y$10$fwPWncMApJLzPMjhkIOcfuM8Oj.6T1xZRwsntewbYyeX4tgocoLuC	jpalacio@unal.edu.co	2020-08-26	08:50:15	Masculino	2	2	Agatha	\N	No
1053855736	Anngie Paola Sánchez Morales	1997-02-21	ansanchezmo	$2y$10$zll.a9H.gYbNM9ZoK7UrAuzFfdh1oFooFjzSSoz6EfLogKgTyi4WO	ansanchezmo@unal.edu.co	2020-08-26	08:55:07	Femenino	2	2	gary	\N	No
1053860219	Andrea Castro	1997-10-18	andcastroga	$2y$10$WUADIVwcfJiqBWh59oKTOeP0uJPQemaeBT9tDqdkriZNJAQbUDUyy	andcastroga@unal.edu.co	2020-08-26	09:25:53	Femenino	2	1	Juliana Montoya	\N	No
1053862979	Juan Diego Velez Arias 	1998-02-24	jveleza	$2y$10$h9QduVBAuNhwvhuD2FcBf.yHgofIQMmNcTtUecoNRxRqPF3SyYUoK	jveleza@unal.edu.co	2020-08-26	08:50:10	Masculino	2	2	Blacky	\N	No
1053863761	Alejandro Álvarez Patiño	1998-12-04	aalvarezpa	$2y$10$atzXrRQshse13YEs3sUiJejKRQ8rIE2rlp0Z3j7wbGCZPDRTGTFtO	aalvarezpa@unal.edu.co	2020-08-26	08:45:44	Masculino	2	2	sasha	\N	No
1053870275	Santiago cubides arias	1999-05-05	scubidesa	$2y$10$c1J9rA8NNyvSYbqhQfVXMu3p/F1pPbB8VGrDUGEra1RYCyfQYzXuG	scubidesa@unal.edu.co	2020-08-26	08:53:03	Masculino	2	2	lenox	\N	No
1053871974	Lorena Cárdenas Aguirre	1999-08-25	locardenasa	$2y$10$gFXntkm/tl1H2wBkEGdayun.7nJjuhb1FiE91WoIAIgKzQ92/g9da	lorenacardenasaguirre@gmail.com	2020-08-26	08:50:01	Femenino	2	2	locky	\N	No
1060655826	Jorge Mario Portela Cifuentes	1998-02-20	jmportelac	$2y$10$V20mkcoJ9q5v.iXlffM9E.rbmS2onQo9nGO/lIrljTQCwFDpqM4B2	jmportelac@unal.edu.co	2020-08-26	08:41:55	Masculino	2	1	tatu	\N	No
1060879126	Diego David Ramos	1995-01-30	ddramoss	$2y$10$jxRr2MA3scv4sREY/GM8F.jnAbMCK15U2tyQiHNYTHLmnd7mqW.1S	ddramoss@unal.edu.co	2020-08-21	17:51:28	Masculino	2	1	pepito	\N	No
1053867032	juan manuel garcia cifuentes	1998-11-10	jugarciaci	$2y$10$wls0MU9ZxAF1lt80v9dtU.z57eLpejN7EbnqFUXXBiHnxD6aPpfnu	jugarciaci@unal.edu.co	2020-08-26	08:46:21	Masculino	2	2	danko	\N	No
1053869341	Nataly Aristizábal gonzalez	1999-03-21	naaristizabal	$2y$10$mYRwCGLDIXODShCHT3aRjev.E27kXasSY2nX/tvYog/uUE8laDB4u	naaristizabal@unal.edu.co	2020-08-26	10:00:36	Femenino	2	2	Tango	\N	No
1000252827	Daniel Felipe Peña Martinez	2001-08-13	dpenama	$2y$10$XfJ1EFueishz.l3j2FQUwuHNJA9h0znkCf4fzLNO/UMhLeVwyX7Hq	dpenama@unal.edu.co	2020-08-26	08:45:25	Masculino	2	2	Dante	\N	No
1002654745	Johan Sebastian Valencia Gil	2001-07-01	jvalenciagi	$2y$10$8DfQQLtj.cYN1oXvqWnQT.xNbf3gahzjJxEWrD9yfOHPgragvRFqS	jvalenciagi@unal.edu.co	2020-08-26	08:48:54	Masculino	2	2	Toby	\N	No
1002670044	dilan zuluaga jimenez	2000-10-23	dizuluagaj	$2y$10$mTxFruij3ss7N7vvtzI./OZ1SUtucIzNR6GtO9FeBp1TtLBB6FuWC	dilanzuluaga9@gmail.com	2020-08-26	08:48:52	Masculino	2	1	ninguno	\N	No
1004508273	SANTIAGO FELIPE QUITIAQUEZ GUACAS	2002-02-16	saquitiaquezg	$2y$10$fpj3mpe0eGU62.UP62Qxve8NBMxPPMpVQj4YiChpwR1CknMgNV2VO	saquitiaquezg@unal.edu.co	2020-08-26	08:41:33	Masculino	2	1	yohan	\N	No
1007568947	Fabian Camilo Alfaro Fierro	2003-01-18	falfarof	$2y$10$9O00.JcKOAkeOwiNhD93zeZNYyjWSAuLWadYJmScydWsTRndrShbi	falfarof@unal.edu.co	2020-08-26	08:49:43	Masculino	2	2	muñeca	\N	No
1053790466	Cristian Ramirez	1988-11-25	Cramirezz	$2y$10$e7aXfc3nfCNEy/wv4AqnS.2Ny3vs2MsPlfRRghE1dy.KCXkidsfye	cramirezz@unal.edu.co	2020-08-26	08:46:36	Masculino	2	1	Alejandro	\N	No
1002566850	Juan Esteban palacio Valencia 	2003-03-28	jpalaciov	$2y$10$m0kbqGFgTNpcfXyVvUhF7e069RJj37WJLIoRMQ8Ld0kJX3UHkLUUS	jpalaciov@unal.edu.co	2020-08-26	15:48:24	Masculino	2	2	Luna	\N	No
1002567315	Alejandro Cardona Monsalve	2001-03-04	Acardona	$2y$10$CpIl8esp4G6N0PcaR.c9Celg67JvdCKqYdG/xp2L8uhIIOF5KxN9C	acardonamo@unal.edu.co	2020-08-27	07:25:14	Masculino	2	3	Portugal	\N	No
1002581139	joan sebastian guarin donato	2003-01-12	joan guarin d	$2y$10$737A5Utg6.ijELdNEMkbhulmEqGBOop6Ryx.hNCy8GdE7s9jCBaXK	jguarind@unal.edu.co	2020-08-26	20:48:16	Masculino	2	2	tomi	\N	No
1002591004	Daniel Marin Jimenez	2001-12-13	dmarinji	$2y$10$rWKRoIhytgdJtpFKI1cl6.DxY21YKQc/fDnx0HOw6lh5Gdgzpb7ja	dmarinji@unal.edu.co	2020-08-26	18:55:29	Masculino	2	2	tovi	\N	No
1002591549	brahian stiven echeverry	2002-10-31	Brahian31	$2y$10$7VrJ9bzKC//Ss8MmliiGg.FwDv0D66zGBZbBXaJ1a0weR/Z0P/MG.	becheverryq@unal.edu.co	2020-08-30	18:24:06	Masculino	2	3	puerto rico	\N	No
1002634921	nicolas holguin giraldo	2002-11-03	nholguin	$2y$10$MtJq/CkJSjeDnuNHBTei1.2za.uvtJjRkG1BkAou2PwaTIoqlYxiy	nholguin@unal.edu.co	2020-08-26	14:19:02	Masculino	2	2	puchis	\N	No
1003689652	Juan David Moreno Guillén	2002-10-21	Jmorenogu	$2y$10$VwUxArEaSBgpguCgNgveTelgR.u5iIYJuE8nYbKBgHw4d/wLoRtgC	jmorenogu@unal.edu.co	2020-08-26	21:04:42	Masculino	2	2	Alaska	\N	No
1005701620	Tatiana Katerine Guzmán Cruz 	2002-04-24	tguzmanc	$2y$10$oYGK69i3NvSEHK.s0aNsDe3TxNDH1mxmU9R5mjk5sg7F6N3JzbhVG	tguzmanc@unal.edu.co	2020-08-26	12:19:41	Femenino	2	2	Pinina	\N	No
1006998171	Oswin Cuaran	2002-02-08	Oswin	$2y$10$0HF3mfleXsnX6NDoEVhmF.xradHYV213Pivru/BsxG1KtMq025zIW	oswin625@gmail.com	2020-08-26	16:23:58	Masculino	2	2	lulu	\N	No
1006999331	Kevin Dario Arevalo Rivera	2001-05-29	karevalor	$2y$10$znBqr7EqpiQk63PnqjQ9auA/wUKcNhRHGZ2xKQ3M/gafb0oD8w.ty	karevalor@unal.edu.co	2020-08-26	18:14:25	Masculino	2	1	jhoan	\N	No
1007233130	Rafael Mejía Zuluaga	2000-06-07	rmejiaz	$2y$10$/Malsuc5RpWNN.yRr9IZt..NVIFqbzQYSWGVmJLf0mQh/yN1yxaZm	rmejiaz@unal.edu.co	2021-11-21	09:55:36	Masculino	7	1	Lorenzo Uribe	\N	No
1007628722	Brayan David Collazos Escobedo	2000-07-09	D@vid	$2y$10$tJbX.YNNbhvtCHM6mx6rpOdzgsa3YP/rz3CuRsVxMV.KE.2YCKBUy	bcollazos@unal.edu.co	2020-08-27	07:24:32	Masculino	2	2	Morgan	\N	No
1053817676	Hans Rivera	1992-03-04	Hans Rivera	$2y$10$v6hyv8G13HaG9AYM7uwfo.1poZFV9TXiZ4E2HQJClvGokyVhoqLvW	hrivera@unal.edu.co	2020-11-09	15:04:38	Masculino	2	3	Manizales	\N	No
1002656353	Juan José Gil 	2001-02-11	jugilo	$2y$10$wMv2NQnsP52H.aYt5QEmLuR3k09T3PVmMLxR2D7e2QrHdm5pzZEMS	jugilo@unal.edu.co	2021-11-22	09:08:45	Masculino	2	1	gaviria	\N	No
1053840867	Jesus David Ramirez Pareja	1995-02-23	Jedramirezpa	$2y$10$gplC/T3.3qO3TeMIbeHLh.BQTP7JRLRrLxIm8uLtT7/xibThI/73a	jedramirezpa@unal.edu.co	2020-08-26	13:56:33	Masculino	2	2	Copito	\N	No
1053841556	Jorge Luis Ordoñez Ospina	1995-04-06	joordonezo	$2y$10$tXGChqQCbpY5MEUieWPgc.sx.4UmNlU.4z8i5Ph4Suor.rG6S/kWS	joordonezo@unal.edu.co	2020-11-09	21:24:58	Masculino	2	1	alejo	\N	No
1053844329	juan sebastiin lopez giraldo	1995-08-21	juslopezgi	$2y$10$JzIwJATvm1KzoacYjTQwE.5IWTzDYoP9vWxZ2TA5vvgboA7SGi92W	juslopezgi@unal.edu.co	2020-08-30	18:16:13	Masculino	2	2	hanna	\N	No
1053863442	Jhon Edward Patiño Patiño	1998-02-20	jhpatinop	$2y$10$oPgBY8YCOaT8YLBxY9ILmu7zlQE7mbKFnB5FdVsT0dHI3clEP/uce	jhpatinop@unal.edu.co	2020-08-26	10:18:30	Masculino	2	1	jhonatan ceballos	\N	No
1053869586	Jeisson Andrés Salazar	1999-03-31	jeasalazarsa	$2y$10$vFVug34umUq60yo2bQz0oeNHX7tHNmIfqdcWCwO5SKHvyjfbnWyDu	jeasalazarsa@unal.edu.co	2020-11-08	16:28:52	Masculino	2	2	Nerón	\N	No
1053871829	Cristian David Bravo Villota	1999-08-08	cdbravov	$2y$10$5YUBnKeztpIYMWECRtoeaem4bCm39vkBf6KVh8IZwZPIQRnfJtFz.	cdbravov@unal.edu.co	2020-11-10	20:26:11	Masculino	2	1	James	\N	No
1053873232	Daniel Fabian Serrano Galvis	1999-11-06	dserranog	$2y$10$mazwCjdo4jidx.Ws2jon/ej7UV0MJ0qQW/PW.nS5VAUKDDRlOEjqq	dserranog@unal.edu.co	2020-11-09	10:36:32	Masculino	2	2	Toby	\N	No
1066720329	stiven payares	2003-09-02	stiven payares	$2y$10$NOJ8kG5f.oGWs//RE4G8OufOn/p26CaLwdmhlmOeaIo1.PO7e3SXi	stivendavid@2019.com	2020-08-26	12:18:35	Masculino	2	1	isacc	\N	No
1067958178	Fabian Corcho	1997-12-03	fcorcho	$2y$10$1u1axNg0xLh/RYvU73MAmeducqoijjfVDLO5XDNMIRqxMfFEdLpMi	fcorcho@unal.edu.co	2020-08-26	11:08:00	Masculino	2	2	Mateo	\N	No
1088599326	Edwin Chenas	1999-04-24	Edwin	$2y$10$9SHb/NbXDzN7OToi7N4k/OgwI73wy5HQnlNi3kAiutk2MUaaJvpl2	edherchenas10@gmail.com	2020-08-26	16:36:48	Masculino	2	1	Brayan Paspuezan	\N	No
1118574095	julian lopez marulanda	1999-05-03	AnezdA	$2y$10$QqiAyzgQbcataiDgQXbk8eFNrXxVP03iBHSHev55vj/DRVFMGxqKm	jullopezm@unal.edu.co	2020-08-31	03:01:27	Masculino	2	3	finlandia	\N	No
1193589331	karol yulieth pavas leiva	2001-07-19	karolpavas	$2y$10$2E.vmx2EbNBOEdbORszw5ejhDPLUeXiB45BUXndSyu3/Mj6mrRzFe	kpavasl@unal.edu.co	2020-11-09	10:38:29	Femenino	2	3	barbados	\N	No
1085544356	Johan Hernando Ocoro Satizabal	2004-06-07	jocoros	$2y$10$BzrRuIeQD1agjbaHgeIZsurM/I/EIqoAXTPrLVHb1THyUfQDZAdWK	jocoros@unal.edu.co	2020-08-27	07:40:53	Masculino	2	2	sam	\N	No
1113686690	Nicolas Pavon Gomez	1997-07-16	npavong	$2y$10$sRmx5mb9RolMGcf/JoBu4.vOEoN.7rXOhHVkcogdZv6Zm88VQIXgq	npavong@unal.edu.co	2020-11-13	14:40:53	Masculino	7	1	Alejandro	\N	No
1193589552	Catherine Andrea Pinzón Giraldo	2003-08-30	cpinzongi	$2y$10$FyqGJ/SKvMHWFpx2BX8D0ufbggOG8.ilFNX4SfL3NLmD7OCeD96xm	cpinzongi@unal.edu.co	2020-08-26	22:32:36	Femenino	5	1	Yuliana	\N	No
1010132887	John Eder Posada Zapata	2000-12-30	jposadaz	$2y$10$05spOX2QFQsD4JLe/mmPyuN50lwlXRE3NHwQ7s4jSx9WNy.VlKceO	jposadaz@unal.edu.co	2020-11-10	11:57:16	Masculino	2	2	luna	\N	No
1053825726	Raúl 	1993-04-02	Ralvc	$2y$10$YxfK3izYI4BOczekx1u.Lu0iFEEzGr1VRyja2vBw7XVBnwsS51O76	ralvc22@unal.edu.co	2020-08-26	18:51:15	Masculino	2	3	New York	\N	No
1053872904	David Zuluaga	1999-10-21	David99	$2y$10$wH9yBAfCwRo44xA0kUqQt.u.l3rWOBC6Oe1VTf0mBsSoiJXvwxOxm	dazuluagaos@unal.edu	2020-08-26	09:18:04	Masculino	10	2	Kyra	\N	No
1000076392	Carlos Andrés Grajales Montoya	2000-02-14	cgrajalesm	$2y$10$doLGQH4GsZJjM7yi2f19K.a4tCKEtabdYXNB2tMkgF6MQ84XSt982	cgrajalesm@unal.edu.co	2020-11-18	11:05:59	Masculino	2	2	Bruno	\N	No
1002544906	Andrés Felipe Vélez Trejos	2002-07-20	Andrés Vélez	$2y$10$vLXX8DchCtYsqfZpgXVge./NUlt5cqrYsfp.nLCIxYWpkEusZiVCW	anvelezt@unal.edu.co	2020-08-26	08:50:40	Masculino	2	3	Portugal	\N	No
1002879104	Alexander Giraldo Narváez	2002-11-16	agiraldon	$2y$10$ljYKOtnMBb066945zNOp4uIlMixr/m057s3tUoRugrCp0gMjwUmtm	agiraldon@unal.edu.co	2020-08-26	08:50:27	Masculino	2	1	Juan	\N	No
1053870754	Maria Camila Martinez Betancur	1999-06-13	mmartinezbe	$2y$10$PXeoXLUtewN.wxNYylo1YOmGI/aJy4SZk7NZx/1Hlfdv.lhxQtK.O	mmartinezbe@unal.edu.co	2020-12-22	10:12:10	Femenino	2	2	lulu	\N	No
1007233006	Juan Esteban Cardozo González 	2000-04-09	Jucardozog	$2y$10$Fx9ShlbVdjyMwAcMH2RjLOEC8lzVdJBuEMQULTC7DAVGDWd/PnkkG	jucardozog@unal.edu.co	2020-08-26	08:48:39	Masculino	2	2	Lupe	\N	No
1110599607	Julio Mario Torres Fandiño	1999-07-19	jutorresf	$2y$10$K/09YX94s4sWdIAdMIwt0OtnCjiv.Q/sippavTwS9E5uhKuLdT.AS	jutorresf@unal.edu.co	2020-11-09	20:22:14	Masculino	2	1	Glorial9@	\N	No
1126602847	Sebastián Gutierrez	2020-08-26	sgutierreszpi	$2y$10$k/t4v2fcWWc/f5fEbGam2uBjGBFGdG6RJhBMun7zFIQYC1g7p2MTi	sgutierrezpi@unal.edu.co	2020-08-26	08:49:41	Masculino	2	3	puerto rico	\N	No
1193557800	Gabriel Eduardo Corrales Villalobos	2002-10-31	gcorrales	$2y$10$5.imNkKO.bJg7iQgFJMm8.COy0C8s/um2LMGmxx3TFzGikrA1YSdS	gabo.eduardo.321@gmail.com	2020-08-26	08:53:23	Masculino	2	2	zeus	\N	No
123456789	Valentina Tabares Morales	2001-11-11	vtabaresm	$2y$10$MFw5wWZhfC6iSL/k8U906ew1mJRjBamyPrBdpbOBqPKw6SUd5eCNi	vtabaresm@gmail.com	2021-02-22	10:20:57	Femenino	2	3	no tengo	\N	Sí
1007445150	Tatiana Alexandra Betancurth Paiba	2001-09-15	tbetancurth	$2y$10$oStkEyAMGfbtq6NOT3.CsOwD70ZCEb78jgQPHnigkA67Up/MuKpMe	tbetancurth@unal.edu.co	2021-11-21	13:41:36	Femenino	2	2	tabata	\N	No
1002938965	Nicolas Giraldo Gil	2003-08-30	ngiraldogi	$2y$10$GOpZLx1NxeDnbK1RXlQrp.tUvAU27raetInb1nZFZsxeVZSMcz6JG	ngiraldogi@unal.edu.co	2021-11-21	21:37:45	Masculino	2	2	Cuqui	\N	No
1002592567	Luis Felipe Velez Garcia	2003-08-28	Luis Felipe Velez Garcia	$2y$10$vpZyp1.akAXqmtSW9I4Gl.9/.nTsdwlPFObWHaEAkAnoMAJaW83r.	luvelezg@unal.edu.co	2021-02-25	08:51:10	Masculino	2	1	Rodrigo	\N	No
1055750069	Juan Sebastian Nieto Castaño	2003-11-26	Juanse	$2y$10$k5z9M2xvfwq8kCmQkFbN0uoIMaeUAiaARoAQiUlDBPnMP5jg3rEQW	jnietoca@unal.edu.co	2021-02-25	08:51:13	Masculino	2	2	Fred	\N	No
1002591341	Juan Camilo Ballesteros	2002-01-23	Balles	$2y$10$RFQAwiLRyEM7e/wCglQrP.CXGCyDUYoBOzndx3jYDAZxMpwXipVxe	jballesterosd@unal.edu.co	2021-11-22	16:18:54	Masculino	2	2	zeus	\N	No
1002654030	Karen Dahiana Perez Quintero 	2003-03-13	kperezq	$2y$10$2M8sGfRxWOXH8AcmhzbTBeDBTJK4FTjdB0L2WdbBLJKrlVNDFu.FO	kperezq@unal.edu.co	2021-02-25	08:51:51	Femenino	2	2	natasha	\N	No
1002566318	Laura quintero cuartas	2001-10-10	lquinterocu	$2y$10$NQtkDkf4eMbRAAQiUyup/.pha3n954hgthRWCu2dguzcozrfKBH7G	lquinterocu@unal.edu.co	2021-11-24	11:13:44	Femenino	2	2	fresa	\N	No
1002635996	Thomas Cardona Mesa	2003-07-21	Thomas Cardona Mesa	$2y$10$ihP6FGGw0S15PBVKDG05hO4FOL2oSFQhkWH6zsJI/ehEZDpZXUGKC	tcardonam@unal.edu.co	2021-02-25	08:52:36	Masculino	2	2	Niño	\N	No
1107058708	Leonardo Pérez Manrique	2002-06-26	leoperezma	$2y$10$sY9VSxvL0bprqjL0f9Ged.cZeF/d846IPZpYa29HiOOdSCHNlU3PW	leoperezma@unal.edu.co	2022-03-10	07:43:09	Masculino	2	1	Marlon	\N	No
1006501238	VIctor Emmanuel Lagos Bermudez	2000-12-28	Vicblocks|	$2y$10$6A96HPUtwkFbVD7oFY7nruQ0/DxsFNhXtbwqkGJkApymBVKsSjkxe	vlagosb@unal.edu.co	2021-02-25	08:53:48	Masculino	2	2	Kaiser	\N	No
1053857294	Daniel Isaac Contreras Barrera	1997-04-29	dicontrerasb	$2y$10$mcy5ZiJQtrnMY6aImsCaKOFZEpxEdMZRL8UnG0aESvhhXNNcvGTyK	dicontrerasb@unal.edu.co	2021-02-25	08:54:07	Masculino	2	2	chapulin	\N	No
1056120654	Juan José Marulanda González	2004-05-04	Jmarulanda	$2y$10$/rBLFKlQDMVE40P1IvrN6.jealhtwTrtr3lyDU3hqdMKcVw/00zce	jmarulandago@unal.edu.co	2022-03-10	07:43:41	Masculino	2	2	Atlas	\N	No
1055751648	David Duque Cardona	2004-10-22	davduque	$2y$10$0/C0u0/8W8YVjfI60sSpAuFK7Lu7hrdAYyNObcZ5PX3C6ZLqWZQDG	davduqueca@unal.edu.co	2022-03-10	07:44:22	Masculino	2	1	teo	\N	No
1232388951	Mariana Isolina Mosquera Hernandez	2005-04-13	mariana	$2y$10$7QaQs.pW9N.nE8C9aIXxTOqJelsZfV88TiMRZ2ZM709.VMFkMJtZu	mamosquerah@unal.edu.co	2022-03-10	07:44:34	Femenino	2	2	valentin	\N	No
1055750581	David Santiago Castañeda Tabares	2004-03-01	davidunu	$2y$10$tHNN6cgHvubLJUvLVLo0jOVz3jZ.pO/2cKj3BS5rMgu0Wf8gBWHqu	dacastaneda@unal.edu.co	2021-02-25	08:55:05	Masculino	2	2	pepe	\N	No
1054858688	Samuel Alzate Morales	2005-05-25	salzatemo	$2y$10$se/0rgrBgjmD4OnQUFcVxOzpWg9viyV1ReLq/EdrniOiQXgXi37ci	salzatemo@unal.edu.co	2021-02-25	08:55:08	Masculino	2	2	Skypper	\N	No
1002799561	Juan Andrés Betancur Trujillo	2002-07-25	Jubetancurt	$2y$10$Wh8C3803l0l33VNLXdrMOOiVLmE0T14S6IecKnPwVqo6F1teRsOKa	jubetancurt@unal.edu.co	2021-02-25	08:55:09	Masculino	2	2	Garritas	\N	No
1002755036	Sebastian Giraldo Montoya	2003-06-30	Sebastian	$2y$10$rT/a5aU/GKKZtw/iNYc9iOltCIQTI1LxbEBu8XSGrNX2EIDLHkDG6	segiraldom@unal.edu.co	2021-02-25	08:55:38	Masculino	2	2	Princesa	\N	No
1002546018	juan sebastian castrillon escobar	2003-04-10	jcastrillone	$2y$10$NPN56DxX85QmEABeAOASP.cG8J9EXZNu/2m3egGyrJiv47D4yPnm6	jcastrillone@unal.edu.co	2021-02-25	08:57:03	Masculino	2	2	lucas	\N	No
1024460126	Brigith López Florian	2003-10-12	blopezfl	$2y$10$FUS8HEC86ernSfzxwTrwNuvrp7veJSL5OGXjx0kEzjhsFnkmueuZa	blopezfl@unal.edu.co	2021-02-25	09:00:18	Femenino	2	2	Cusco	\N	No
1002654063	Fredy Andrés Aguirre Uribe	2003-04-15	Fredy A. uwu	$2y$10$dPojz/nNQOmyXqBUYdNlhugUAT8sQ46DI078GXFLCd9/GFxbYPsvO	faguirreu@unal.edu.co	2021-02-25	08:53:09	Masculino	2	2	luna	\N	No
1000806344	Julian Alejandro Pinzon Rubiano	2003-06-21	jupinzonru	$2y$10$yScbleAIFyM/x/ipR.Zvxe7y9c9HBZjQPcaThSmgnkD3zP9E/pgiS	jupinzonru@unal.edu.co	2021-02-25	09:48:06	Masculino	2	1	martin	\N	No
1056120203	Juan David Franco Perez	2004-01-12	jufrancop	$2y$10$0j.EP15XrNmVHSv1YqPAmOf6JxOiLtynVsX4/pTlQH8ngBpJuaeGy	jufrancop@unal.edu.co	2021-02-25	10:53:32	Masculino	2	1	Luis	\N	No
1053765252	Luis Miguel López Uribe	2004-03-07	lulopezu	$2y$10$f5WUTrQT5J7e3eOa2UZN0OXYBJGF.kdvBoa4hHnZvDNAHBBTgWlRm	lulopezu@unal.edu.co	2021-02-25	10:57:48	Masculino	2	2	rocky	\N	No
1055750531	Jerónimo Grajales Pérez	2004-03-02	jgrajalesp	$2y$10$Rou/IXx7NJO29mXd.c1tx.Cv/NGY7mSzWOynNqw9ebQWAV78WVRfq	jgrajalesp@unal.edu.co	2021-02-25	10:58:04	Masculino	2	3	caracas	\N	No
10002547048	smartinezse	2001-02-05	smartinezse	$2y$10$baVxLEDJlq0wMNwWd6.XBu9eXGeKhJRuzEAusaul8.wHHw86KzG0u	smartinezse@unal.edu.co	2021-02-25	10:59:32	Masculino	2	1	Diego	\N	No
1114952117	Veronica Rendon  Florez	2004-01-31	Veronica	$2y$10$pvNW/jVT.fwZPG.JsGDqH.sKTdwnvTP2i3y9h/aIQJVLsKce2EeRC	vrendonf@unal.edu.co	2021-02-25	12:54:13	Femenino	2	2	Flor	\N	No
1003568721	   Juan Mario Rojas Rojas 	2003-10-17	juanmarior	$2y$10$Yrn7NqM.t4gwx1IxX0RnFOo4rn2Ag/rYqhEwHs1dEviT01YTGSIMW	jrojasroj@unal.edu.co	2021-02-25	14:05:06	Masculino	2	2	max	\N	No
1192728915	Santiago Castrillón Salazar	2002-09-19	scastrillons	$2y$10$nniUjhjfWTmWWvhnq7expOMocWTal4FTYCLrAZG8BVhf3Y4Gh7d2G	scastrillons@unal.edu.co	2021-02-25	16:58:47	Masculino	2	2	susy	\N	No
1002542442	Santiago Vargas Londoño	2000-12-19	SantiagoVargasLondoño	$2y$10$A1/Kpvm6zJYKDw/ZmXoIXOA69jH2JUKYqeWBJQ/VZry7drD7GXYlu	sanvargaslo@unal.edu.co	2021-02-25	17:00:30	Masculino	2	1	Andrea	\N	No
1002566491	Maria Fernanda Gomez Narvaez	2002-06-26	mafe	$2y$10$GzFnITA25frOBezlvfSIy.JHZgq21VdLHasAHBMQycvxoGQLHGGWm	mgomezna@unal.edu.co	2021-02-26	09:00:03	Femenino	2	2	luna	\N	No
1004776353	David Moreno Gaviria	2001-09-30	davmorenoga	$2y$10$QnFJ2O7U/kkBfIc7L0VjaOHbYF20VY2bBsSl4/rO4ohC3Sw3sJiJK	davmorenoga@unal.edu.co	2021-02-26	09:04:28	Masculino	2	1	stiven jimenez	\N	No
1053782025	Valentina Tabares Morales	1987-10-17	vtabaresm	$2y$10$2xVYW3KxOkbJ7AOlOezqauZ.YidlGlbyHjbVqP97JaHJXQarp4466	vtabaresm@unal.edu.co	2021-02-26	15:29:40	Femenino	2	2	Lupita	\N	No
1113696292	thadal franzel fernando molano macca	1999-04-09	thadal 	$2y$10$EYqPk.MPUFPF4htaDAH8qumPKSzhmNfx9zvCmki1jes7lNpTukObS	tmolano@unal.edu.co	2021-02-26	15:31:42	Masculino	2	1	morales	\N	No
1129292022	diana	1991-06-20	diana	$2y$10$iQ7X38y1u3KQoN8dGY37m.ew0x/K9Xo8xm5Vnr08cujsgIJMJfrpm	dianavanessa121094@gmail.com	2021-02-26	15:42:33	Femenino	2	2	mechitas	\N	Sí
1055358194	David Gutierrez Yepes	2004-02-28	dgutierrezy	$2y$10$bU.YvAWQUHlHFCBQDAytxurDC4tHnPiN/FlGvX4TE89O3LkDsBFMG	dgutierrezy@unal.edu.co	2021-02-26	19:12:28	Masculino	2	2	Rufo	\N	No
1125783639	Mariana Romero Medina	2004-03-28	marromerome	$2y$10$fhEzr9RaLfFmVgPBNNpM.ukAcTyQD0XOVzIgWu993/gRi0Xde.eTC	marromerome@unal.edu.co	2021-02-26	20:34:48	Femenino	2	1	Sara	\N	No
1002592787	juan fernando martelo 	2001-03-27	jmartelod	$2y$10$.mverURsb0YXx3b.14mS6O8Wu7jG4va8AjdzzBeHMRScFqiKDgpHW	jmartelod@unal.edu.co	2021-02-26	23:43:44	Masculino	2	2	mashca	\N	No
1055751675	Mariana Hormaza Cardona	2004-10-27	mhormaza	$2y$10$h6sxKRzchFmBii95HmTjROgX3zGiDZJeNw5GDzpWwcVCsjEGc02DS	mhormaza@unal.edu.co	2021-02-27	12:33:14	Femenino	2	2	Ramitas	\N	No
1053854792	David López Jiménez	1997-01-08	dlopezji	$2y$10$J76pP5hikbTkK2ikVuezVOdiR2FtQ1K0xLJzIqSCiiNI2dRIIhcw2	dlopezji@unal.edu.co	2021-02-27	15:36:17	Masculino	2	2	coco	\N	No
1054856729	Oscar Alejandro Ramirez Rodriguez	2004-05-06	Oscar Ramirez	$2y$10$LXrYueikz7as5s2mBhzSQu.QsyevkwjtVFZu.IOObLnBEw4YhUwP2	oramirezr@unal.edu.co	2021-02-28	11:35:22	Masculino	2	1	Harold	\N	No
1067837105	Santiago Martinez Agudelo	2003-07-11	Zaan	$2y$10$1up/Gf/NOj7rdqoNopA8VefZU6mi1YX70ujk27sunysZaGqTuWrMK	samartineza@unal.edu.co	2021-02-28	13:27:52	Masculino	2	1	zerna	\N	No
1002576444	Manuel Roberto Castillo Galvis	2002-01-17	macastilloga	$2y$10$LjkZ4TmJuaDt/a74ms/oc.jDSUIuOKry9i8AWbtn4IzpMpo3cs5Qq	macastilloga@unal.edu.co	2021-02-28	21:42:22	Masculino	2	2	toby	\N	No
1007672092	Juan Esteban Bedoya Nieto	2001-07-15	jubedoyan	$2y$10$bbughrE0CvV3hepFrTeAueUHdKE0x6NLeBdr/Eqq.DsnMoDQvnlQu	jubedoyan@unal.edu.co	2021-02-28	22:19:19	Masculino	2	3	pais	\N	No
1002566413	Juan Valderrama	2002-04-12	juvalderrama	$2y$10$bpzTVfF8CueXewiSjoh0guzmhcC9MaJBylv04kzRKcCZKfPpJnTSm	juvalderrama@unal.edu.co	2021-03-01	02:18:18	Masculino	2	2	Katy	\N	No
1002938790	Jonathan Valencia	2002-07-15	Jonathan 	$2y$10$6lxLZWtTUrjybUU9TweUTe3YyfpnV/LYHDP/cnoTfvk1zPvQw23n.	jovalenciaga@unal.edu.co	2021-03-01	09:39:47	Masculino	2	3	Manizales 	\N	No
1002817769	María José Giraldo Gutiérrez	2001-08-01	maria	$2y$10$vBN2mKWkmqveQdUsoXJufeTZTNJMXlJR5lqxI7iVnesXpF.0b9Zhm	mgiraldogu@unal.edu.co	2021-11-21	14:22:51	Femenino	2	2	Brayan	\N	No
1060597339	Daniel Fernando López López	1998-10-19	dflopezlo	$2y$10$uLUd8RlQzdFwl/NuZKZ/eeOQroYRi3TUoUBFY1f04BhizgYgkNsHS	dflopezlo@unal.edu.co	2021-11-21	21:58:17	Masculino	2	2	Dante	\N	No
1002944640	Diana Soffia Martinez	2001-02-27	dimartinezp	$2y$10$KIFRGxOVbknTAlVzpLh6gegG3sBEJ2Q2T2I2yvtQhK22h42DCVDjG	dimartinezp@unal.edu.co	2021-11-23	21:46:20	Femenino	2	2	sandy	\N	No
1193568820	Luis Felipe Giraldo Ortega	2003-10-11	Felipe Giraldo	$2y$10$14ajXGcJz81QZ92ynhNJUeio6o7CQareyd2cUxuoPyy6KJYl8rx3i	lgiraldoo@unal.edu.co	2021-03-01	14:23:41	Masculino	2	1	Camila	\N	No
1006117326	Laura Daniela Muñoz Cupitra	20001-03-06	lmunozcu	$2y$10$XzfUdXVGhMiwnJzochPheelec8LYq8GUu433AA/aELq8OHwmCz.Um	lmunozcu@unal.edu.co	2021-03-01	15:13:04	Femenino	2	2	Luna	\N	No
1007286825	Santiago Buitrago Osorio	2000-03-09	sbuitragoo	$2y$10$SYAASgko4z2KNHVfc0shb.q8/lFessX3iWbvdGKoyrLUhD5HVA7JS	sbuitragoo@unal.edu.co	2021-11-30	09:49:22	Masculino	7	1	Mateo	\N	No
1004567751	Juan Manuel Clavijo	2003-11-05	juclavijom	$2y$10$/9pb/lVSh767k4VEAcW8AeRiFTI1o3DgS4qd3xPMwhJBSx/Vph7mK	juclavijom@unal.edu.co	2022-03-10	07:43:11	Masculino	2	2	igor	\N	No
1006881524	Joseph Londoño Emiliani	2003-08-15	JosephLondoño	$2y$10$uhL4.Nch0NOwJkx7nKErHOeAY5x9Yi7aVQ3pe2TXaUg0chG.Fy5dS	jolondonoe@unal.edu.co	2022-03-10	07:44:04	Masculino	2	3	Los Angeles	\N	No
1004831441	miguel angel angarita rincon	2003-08-17	Miguel	$2y$10$syI21OuEtS92ybryekMhRe1CnWQOXjS9i7hkEvjbOg1bZUh6IsTxK	miangaritar@unal.edu.co	2022-03-10	07:44:24	Masculino	2	2	Matias	\N	No
1055752179	Juan sebastian zapata	2005-02-16	juazapataza	$2y$10$mk8NFrANx3LIF7haSKRE5OIn/GkDNaMrRUlQcjyZaUZ6l7K2ofBTS	juazapataza@unal.edu.co	2022-03-10	07:44:39	Masculino	2	2	zeus	\N	No
1055752118	Andrés Felipe Cardona Molina	2005-01-29	andrescardona	$2y$10$BMyKWOVWK5U3qH/tZJ2hHOBi/sca0o989FJVJHuG4ZRINBcDq.raG	ancardonamo@unal.edu.co	2022-03-10	07:45:22	Masculino	2	2	Lukas	\N	No
1053869969	Daniel Israel Alvarez Echeverri	1999-04-23	Daniel	$2y$10$xrSTUupjv6XlNxFhmX8t4.m0zO6iXNLDEPtiWsb0DoA.gLN9zNolm	dalvareze@unal.edu.co	2022-03-10	07:45:52	Masculino	2	2	tigre	\N	No
1056121172	Miguel Angel Betancourth	2004-10-06	Miguel Angel	$2y$10$K1BcPtJtfEAFcS7l52CA/OJXvSjmVakd.pnuE7bauVEqFrK98Ah5O	mibetancourhh@unal.edu.co	2022-03-10	07:46:38	Masculino	2	2	Dulce	\N	No
1007233501	santiago salgado martinez	2000-06-10	ssalgadom	$2y$10$9.L23xCIURN7lF//ITsB5O131.t/kSNf4vB/lBTSf8Kv2TCimbCTm	ssalgadom@unal.edu.co	2021-03-01	18:16:25	Masculino	2	3	mexico	\N	No
1004550693	Henry Santiago Mejía Florez 	2003-09-28	hmejiaf	$2y$10$T3OJjWhMyyzAny6VSEt.0OKa06nFw1sLl0iSZGCLX0c1YQtegdfLu	hmejiaf@unal.edu.co	2021-03-01	21:04:13	Masculino	2	2	Niña	\N	No
1053864796	Nicolas Arango Gomez	1998-06-30	Nicolas	$2y$10$sARHn3NkiPLGxLxbKIy2Dep59z.grtQqF6IAG7YJ0qWeDzeP.HoTS	niarangog@unal.edu.co	2021-03-01	21:04:36	Masculino	7	2	mono	\N	No
1002654903	Paula Andrea Taborda Montes	2001-05-02	ptabordam	$2y$10$8juVxKsgdoczU9hR4nU6juhf2l/OG9BbMT0lNYBZ8alR5Cfygo6zy	ptabordam@unal.edu.co	2021-03-01	21:05:52	Femenino	2	1	Maritza Ceballos	\N	No
1053872060	Juan González Navarro	1999-05-31	jugonzalezn	$2y$10$kJDdOq7W/qVrUdMjDfjY5eK8Ft1vbiJKDo8HbL5.SM8nedNbVYLd6	jugonzalezn@unal.edu.co	2021-03-01	21:09:02	Masculino	2	2	Zeus	\N	No
1128627638	Diana Patricia Quintero Lorza	1996-08-23	dpquinterol	$2y$10$A3meaqOgg5rum.6idZoaM.nNm2nYOxMkplQ8VXOuWSBFduOlgm6BG	dpquinterol@unal.edu.co	2021-03-01	21:45:37	Femenino	2	2	luna	\N	No
1056120201	Manuela Gama peña	2004-01-04	Mgama	$2y$10$K/kkAYLoIZb3Hd2nbJ6rf.YQIbIm/pPdA1Y8Lme9wSApGKRKkqAaq	mgama1@unal.edu.co	2021-03-01	22:01:37	Femenino	2	3	Canada	\N	No
1055751632	JESSICA GIL 	2004-10-05	JESSICA G	$2y$10$QwHbM2e.8TvaFH4BQu0u/.Bzvv4fgsmydnIKCGpatbWbouuR.GU9u	jegild@unal.edu.co	2022-03-10	07:50:20	Femenino	2	2	LUCI	\N	No
1002634007	Cristian Felipe Hernandez Zuluaga	2001-12-14	Chernandezz	$2y$10$AS8hvXQeA61wB4sHfgD/2uXsRv6rqCoOavs.ZVc5TQZ2ptXpqYVRi	chernandezz@unal.edu.co	2021-11-21	20:36:09	Masculino	2	2	Simon	\N	No
1007233298	Erik Palacio Castellanos	2000-06-17	Erik	$2y$10$cJjr6b1Wh.lwmqZYIykLTunzJz/h4Xc3cQhL2WDfgPvkHpmlo0SI2	erpalacioc@unal.edu.co	2021-03-01	22:24:15	Masculino	2	1	David 	\N	No
100681607	Maicol Estiben Egas Jacanamejoy	2000-03-28	megas	$2y$10$t96KYX6kl1L9/Dhh1yiOhO0j6teyJOvFZzbIj.tghqFwAjganlHpC	megas@unal.edu.co	2021-11-21	22:58:52	Masculino	2	1	David	\N	No
1053872257	Santiago Aristizabal	1999-09-17	saaristizabalco	$2y$10$9YvFOtUqj/.g59Mtrj2qneaPPc7lpjMeF8tvwGEY09ReRIh8PYIPO	saaristizabalco@unal.edu.co	2021-03-01	22:36:04	Masculino	2	2	luna	\N	No
1056120202	Manuela gama peña	2004-01-01	mgama	$2y$10$7B2b7s/mVGyJgQEm4k6PQ.XVnVBp2nI0ZehBZslQ0WfuGyufvoL5e	mgama@unal.udu.co	2021-03-01	22:26:17	Femenino	2	2	luna	\N	No
1053860467	Juan Esteban	1997-11-25	jevallejod	$2y$10$FXtHV230P9z9Mutsr4LcXOxq6sE0sGSsi1Nu5iuWYr8nna/G7.CmS	jevallejod@unal.edu.co	2021-03-01	23:41:52	Masculino	2	1	Camilo Moncada	\N	No
1192904131	Dylan Andres Ruda Giraldo	2001-01-04	druda	$2y$10$CefKwTJyu3BsFjA/Xv3goOqwMevzUHq51m1qGChvX0sotHb6PpD.i	druda@unal.edu.co	2021-03-02	01:54:38	Masculino	2	2	niña	\N	No
1193044131	Juan david gallego.marin	2002-11-29	Jugallegoma	$2y$10$2b8pTZWEnSZ/zIBIQMBo/.BzxOwYlf2LT8zXU/bxz6lNWv.6rEOFq	jugallegoma@unal.edu.co	2021-03-02	06:58:13	Masculino	2	3	Colombia	\N	No
1113695742	David Anibal Rodriguez Caicedo	1999-02-16	davrodriguez	$2y$10$M40DAmLtr/1Jo0E3m3gTP..rG4sCp29I9JCQRxkZFZV3OX5D6uExS	davrodriguez@unal.edu.co	2021-03-02	07:09:16	Masculino	2	1	Imues	\N	No
1056120138	Juan Esteban Agudelo Burgos	2003-12-24	juagudelobu	$2y$10$ChQBqZvJST6rxLXj5fYEhuXUDbLR2rbBhCLoOLtjRXSQQmAxKkNBO	juagudelobu@unal.edu.co	2021-03-02	07:18:35	Masculino	2	1	Hermano	\N	No
1193209085	Paulina Ramirez	2003-07-27	pauramirezca	$2y$10$X.e.vZx7L2RywgT9twJBbewaOfm7n/YthV//039phOpW/BuV0FKEC	pauramirezca@unal.edu.co	2021-03-02	07:27:05	Femenino	2	2	Jazmin	\N	No
1007233138	Daniel Felipe Mejía Ríos	2000-05-07	dmejiari	$2y$10$tu./3YtcFQ5rPYrezrM2a.46dQqYNoa7BqX0kxUWqskgMQXt4sQAi	dmejiari@unal.edu.co	2021-03-02	08:56:46	Masculino	2	3	Manizales	\N	No
1053863825	Juan Camilo Naranjo Corrales	1998-04-23	jcnaranjoc	$2y$10$tSvCaNhPPy.p9Z4FJ/6Mq.lyS9XQsN0YlLcipEYag6R.ugseYo8IO	jcnaranjoc@unal.edu.co	2021-03-02	10:44:26	Masculino	2	2	Barbie	\N	No
1192904082	Víctor Jaramillo Valencia	2000-12-17	vjaramillov	$2y$10$pv5QDpRQBP7mjfdGH6Zeou.7O3SsOEbTI3PehlVW/9Efh0pR/MI9y	vjaramillov@unal.edu.co	2021-03-02	13:18:11	Masculino	7	3	Polonia	\N	No
1004191436	Cristhian Mateo	2002-07-13	Mateo	$2y$10$ZiNYQAyAQBSyyltwa3Us/OvjYx7Joraj9.z1UIFL0TO3z8gXtLVFG	calmeidag@unal.edu.co	2021-03-02	13:39:43	Masculino	7	1	Juan	\N	No
1015431061	esteban rodriguez muñoz	1992-05-10	erodriguez	$2y$10$69aMUBzVVqVL/t4haEBnUew3RUSjeOtb0ySh5N15OpXenHQwpXL9q	erodriguezmu@unal.edu.co	2021-03-02	14:36:19	Masculino	2	1	juan camilo	\N	No
0000000000	Elizabeth Escobar	1989-01-02	ee	$2y$10$zpdCkNHy0hd6fndi3Ma2mOQb17E.Yc/FlWT22khVyZmr8G/GwIbhy	elescobargo@unal.edu.co	2021-03-02	17:40:10	Femenino	2	2	verde	\N	No
1007513044	Melany Julieth Salcedo Ceballos 	2000-08-10	msalcedo	$2y$10$1bs7.8YGJFxJKqI0wKiFyOnLH9x63nO1GI6vmOhG25wm3df0lVrUu	msalcedo@unal.edu.co	2021-03-02	19:15:27	Femenino	7	2	Luna	\N	No
1054992077	Andrés Felipe Posada Gamba	1991-06-25	afposadag	$2y$10$QAOHTOViGrqkLS9qlb9VfOcywdMGhUB7hlLl9WW8VCz9XQOHMWvZC	afposadag@unal.edu.co	2021-03-02	19:50:51	Masculino	2	2	Negro	\N	No
1004508347	Diego Ezequiel Noguera Paz	2002-02-14	dnoguera	$2y$10$Fd5M/Yh2Ra/YNLjPvn8fjO8MZDNlevtyD5fbKMDPQbcFzHJ0rLXHS	denoguera@unal.edu.co	2021-03-02	19:54:42	Masculino	6	1	maya	\N	No
1085952236	Danny Javier Vasquez Ceron	1999-07-05	djvasquezce	$2y$10$fW5hJfmZ8xZtbwM3o2yzKuk0zM9y.HEo8w3EEfg4mlhGuusbwrzx6	djvasquezce@unal.edu.co	2021-03-02	20:06:58	Masculino	2	1	Danny	\N	No
1006407291	JUAN CARLOS PIMIENTO	2001-09-03	Pimiento	$2y$10$Gn7SBn5ClUNy6FL0.eAAvOcw0TbftUOChU/D7DNuKY5Rzf4McWSLu	jpimiento@unal.edu.co	2021-03-04	09:25:57	Masculino	9	2	Caty	\N	No
1002633963	Julián Pachón Castrillón	2001-12-08	jupachon	$2y$10$6WW49cHGy2KyA2JICLFVruhORBFZnjNW9R/xuDwUMhzps1YvHGhcS	jupachon@unal.edu.co	2021-03-07	21:17:50	Masculino	2	2	Adolfo	\N	No
1053828905	José Albeiro Montes Gil	1993-08-30	joamontesgi	$2y$10$i9LxiJ4bd1qBivKDNVFtIe/3l48qHXRe/QpWXMwj7opmK3HOTrQEa	joamontesgi@unal.edu.co	2021-02-22	10:26:41	Masculino	2	1	ninguno	\N	Sí
1060653961	Luis Felipe García Arias	1995-08-09	lufgarciaar	$2y$10$fT/gVt6r7pGvLOCmUULu5Obl7I6zGrsDEMpBwzvLb7jT.xkyVjcjC	lufgarciaar@unal.edu.co	2021-03-23	17:43:39	Masculino	12	3	Manizales	\N	No
1004728747	leider Andres Bravo	2000-11-30	lbravor	$2y$10$oISb0K.XqXYnZH11fvk6zODNDL.Tgyj0DhSMcVicexXqjQrbl2W92	lbravor@unal.edu.co	2021-03-23	18:03:10	Masculino	2	2	betoben	\N	No
1053865271	Camilo Pelaez	1998-07-31	Cpelaezg	$2y$10$KKH1O3knME0AtwamgwvOeuy85UEYAWlKMvJPm4utgOYFSUJD1Fe9.	cpelaezg@unal.edu.co	2021-03-23	19:01:18	Masculino	7	2	dino	\N	No
1010114825	NICOLAS ALONSO SUAREZ RODRIGUEZ	2002-02-08	nasuarezro	$2y$10$5b8xCTadQaBaKhR./G6SUu6LzVpA39eENK1i9K9S3iyjgvbsenHt2	nicholass12345@gmail.com	2021-03-23	23:57:15	Masculino	2	2	Mateo	\N	No
1053873179	Sergio Arenas	1999-11-03	searenasm	$2y$10$olbRKWP3GignHwDHU.k3f.k81D0ke9ZXN.4bsfGsq4e7QfxImBnIW	searenasm@unal.edu.co	2021-11-24	00:29:45	Masculino	2	1	Juan Camilo	\N	No
1055750988	Juan Miguel Atehortúa Camargo	2004-06-06	jatehortuac	$2y$10$yj/U.Epii1GH1L85HCgxku/jLZWr0CSvzAMAJt.RZnr7MfU.IHtQ6	jatehortuac@unal.edu.co	2022-03-10	07:42:31	Masculino	2	2	lucas	\N	No
1054857016	David Ramírez	2004-07-07	davramirezme	$2y$10$imJghWdeVOHM0hAa0l8zl.Y.uO1gOH1qKcttP4HvvkiWS7WZfvvuq	davramirezme@unal.edu.co	2022-03-10	07:43:25	Masculino	2	2	lucky	\N	No
1000130209	María Fernanda Tenorio Salas	2002-12-14	Maria	$2y$10$ergVnOOO4D9IppvfkccIKOzIcNtSrKzxczRDKiJcuHOa1XppI1I6C	matenorios@unal.edu.co	2022-03-10	07:44:12	Femenino	2	1	Mendez	\N	No
1053865561	Julian David Pulgarin Pirazan	1998-08-22	jdpulgarinp	$2y$10$Kq6pvMTQWxsabJtyQPBDt.wn4/r3wLFeIiUnD39spXcoxKLtq1unq	jdpulgarinp@unal.edu.co	2021-03-24	10:45:41	Masculino	2	2	chata	\N	No
1077876513	JUAN JOSE BELTRAN GONZALEZ	1998-08-28	jjbeltrango	$2y$10$svPOd0FAAFGgbYZvBPpSreqEVk7cMRLwnXF7d9hy8e3nwycoxikbK	jjbeltrango@unal.edu.co	2021-03-24	15:21:49	Masculino	6	2	NERON	\N	No
1004561711	Alex Orlando Muñoz Ramos	2001-12-27	almunozr	$2y$10$lGLqF0HiBU3hUo4xakDt8e03nZvkJEH9a4rWMm2dBE/InBjxeaRvC	almunozr@unal.edu.co	2021-04-01	17:48:57	Masculino	2	1	Bayron	\N	No
1094889977	Santiago Blandón Forero 	2005-07-13	Blandon 	$2y$10$rrUtIA/7ypa1xqh5PTxWhO9vLL9unvwjlp6L25DGftrWgfMEZ4aEO	sblandonf@unal.edu.co	2022-03-10	07:44:25	Masculino	2	2	Walter	\N	No
1010156654	Dayane Michelle Ceballos Cardona	2001-09-20	Michelle	$2y$10$Nz3EXAbugdT5VOZWOhW34.3R9EM/pQ5Nty.asF1N2qgVxaFYpBlCq	dceballosc@unal.edu.co	2021-11-21	23:31:18	Femenino	2	2	yiyo	\N	No
1002637036	Daniel Cardona Osorio	2001-09-07	Daniel Cardona	$2y$10$u2swTrrRissBVBAB3RFowuT2QVuCb0ezkdLA5Cr.G7gHmDkUWzSpm	dacardonao@unal.edu.co	2021-11-24	08:25:41	Masculino	2	3	Appleton, Wisconsin	\N	No
1006121493	Juan Andres Frasser Valdes	2001-09-06	juan.frasser	$2y$10$LgNmAPXpu8XBEvUz9.DHZOreGxJ7lTK2H4cfGEhb4MzCUaJ7H65m6	jfrasserv@unal.edu.co	2022-03-10	07:42:31	Masculino	2	1	Sebastian	\N	No
1032393084	Santiago Quintero Giraldo	2005-04-26	squinterogi	$2y$10$qLwyDdk8fKW0za2aaE3SdeOG62buv7H5Ber2K1LSbBbhcdkwh8xP2	squinterogi@unal.edu.co	2022-03-10	07:43:36	Masculino	2	1	Juliana Casadiego	\N	No
1002566183	Mateo Ospina Jaramillo	2003-08-18	mospinaj	$2y$10$ez1TQF8GPjEIZToooHbEM.7ooHlorhA9tVLRuV2TbCpZijz82SLHa	mospinaj@unal.edu.co	2021-04-05	12:50:44	Masculino	2	1	laurita	\N	No
1002459377	Stephanie Alexandra Torres Calixto	2002-06-23	Stephanie	$2y$10$mK2crDVpU7s2qJnqK8QtvOHuxu4voIH1zszeLrnHH9XEGbliofIzW	sttorresc@unal.edu.co	2022-03-10	07:44:13	Femenino	2	2	princesa	\N	No
1002633039	Alejandro Rodríguez 	2002-04-30	Alejandro	$2y$10$H/.b/tDtfEwPbElfWdjHeeY4Y99xFjFi26fpvc1UXhqO2uN5BGKDa	alerodriguezpi@unal.edu.co	2022-03-10	07:44:28	Masculino	2	3	inglaterra	\N	No
1086298059	Gustavo Cesar Gomez Constain 	2003-10-01	Gustavo Cesar 	$2y$10$ur.xugYvJOyWqlZXGrIu0.wng5X85nE3ntvh4fr2F7Wsl.zLvZuni	gugomezc@unal.edu.co	2022-03-10	07:44:43	Masculino	2	1	Daniel 	\N	No
1053837753	vanessa sanchez	1994-10-12	vanessa sanchez	$2y$10$zVQxTGkfxpiELHNxrVbMVu7m2mj4UB65iAfeFeKE6yzziUvlMxvVe	vanessa121094@gmail.com	2020-12-09	15:54:48	Femenino	2	2	lunita	\N	Sí
1007233436	Daniel Santiago Gaviria Galvis	2000-06-20	dgaviriag	$2y$10$5g4SNtOrS2cHPRUhsOJ.Oe1cFlX4ZSpi98jVdlxiWTCYN1EybECD6	dgaviriag@unal.edu.co	2021-04-17	22:04:57	Masculino	2	3	Los Angeles	\N	No
1060270184	Valentina Mejia Rios	1998-11-07	vamejiari	$2y$10$smAwO10VwxtrGe4SKunCruRP9lHd0WVenKsw0wl6uSfPjs7ruvaIy	vamejiari@unal.edu.co	2021-04-26	11:30:04	Femenino	2	2	Juano	\N	No
1002634179	MAICOL	2003-04-21	Maicol	$2y$10$h.dhtBFJpD9XsAUwxhA1uedhdSWbEoXPu/bVVL.Oq8OFY9PK6D8uG	maranzazul@unal.edu.co	2022-03-10	07:45:31	Masculino	2	2	niño	\N	No
1055358298	Sergio Alejandro Gaitán Quintero	2004-05-03	sergiogaitan	$2y$10$u0YZs/PPQJHKeCZpnfgBlO2rc0AsM08DXqSafSFmqebiszcvwOMMi	sgaitanq@unal.edu.co	2022-03-10	07:45:58	Masculino	2	1	Luis Henao	\N	No
1056120807	Juan Esteban Graell Alzate 	2004-06-12	jgraell	$2y$10$8tjMDaqhwQXKLglkq6E8He7VnRj3vP9/kGuAW.wKVhuAQC8IMlKGW	jgraell@unal.edu.co	2022-03-10	07:46:08	Masculino	2	2	Lola	\N	No
1055751716	Juan Manuel Bacca largo	2022-11-06	jbaccal	$2y$10$jPQ5nDoyvPqWi2oqPacMn.s6PG0f.W0hnhHyidflvjrOat3.KdKSa	jbaccal@unal.edu.co	2022-03-10	07:46:39	Masculino	2	2	lucas	\N	No
1192800056	Esteban Gaviria	2001-05-04	Esteban	$2y$10$ka/uiLvfXXQ2b5VKfB5/a.EaUiN6KemczjpkYjg.DI7OK791sTqEO	egaviria@unal.edu.co	2022-03-10	07:50:34	Masculino	11	1	esteban	\N	No
1007232295	JHONATAN SANTIAGO PARRA SANCHEZ	2000-05-18	jparrasa	$2y$10$bX/2uBKl6q0Zhiynb4..EuNvo.HU8E1mZspKCGYd9a7OporRzbXry	jparrasa@unal.edu.co	2021-04-26	17:31:51	Masculino	2	2	cristal	\N	No
1053835141	elizabeth carreño	1994-05-31	Elizabeth	$2y$10$rJ0JdbWlFSOOJT6phFMEOemPfdnL3YEKbJHr/9OxjamXQs2salZ5u	ecarreno@unal.edu.co	2021-04-27	14:47:42	Femenino	2	1	mariana	\N	No
1000654412	Brayan González Ortiz 	2022-05-31	Brayan1831	$2y$10$Qu6e9dOXuhsKdRg0FkzELOHReRZ9NcJP.soSYjItfGmss.Gh/TCzm	brayan.gonzalez@ucp.edu.co	2022-05-16	21:08:20	Masculino	12	2	Juanita	\N	No
1004699368	Juan Paulo Duque Vargas	2000-06-22	Juan Paulo	$2y$10$ql6bsRoCrpMRRigtVnzi.ufCo7jJ1CkPv/TQVMoO4wxASEce3vQXe	juan7.duque@ucp.edu.co	2022-05-16	21:09:30	Masculino	12	1	Diego Fernando	\N	No
1004776699	Mariana Valencia Torres	2002-07-27	Mariana Valencia 	$2y$10$0u5YovKBLK.FQtwGjnYMW.Z5Ohl7fXpLkJKjX9GiKdZNrA2yxKIim	mariana2.valencia@ucp.edu.co	2022-05-16	21:15:32	Femenino	12	1	Tris	\N	No
1004752502	Jhony Mosquera Isaza 	2000-11-05	Jhony.mol	$2y$10$E4lRNUGQFzZJUvrJq6gitOR8naf4fkiL6gHDxOMnspVynYKzuX/BC	jhony.mosquera@ucp.edu.co	2022-05-17	20:13:02	Masculino	12	1	Guarumo	\N	No
1004359744	Vladimir Manuel Galleguillos Arenas	2002-07-28	Vladimir Arenas	$2y$10$dRn46kjJrz3dxBYqCEjUWeKy4wff5xZmClgK6OfjVbdD9jK0F.4Um	VladiA801@gmail.com	2022-05-17	21:26:45	Masculino	12	2	Anko	\N	No
7547355	nestor duque	1970-01-01	nestord	$2y$10$fdU9kC7ocC8p5L4QAIRHb.lsm0XTsvsI0hgUzb6fus?	nestor.dario@gmail.com	2021-03-22	22:52:15	Masculino	2	2	lionte	\N	No
963852741	Néstor Darío	2000-01-01	ndduquem	$2y$10$hjut4rmexZP0dTu.xUlOWOdOowosQKnCmcn1AxTjJP.tekvImVr.i	ndduqueme@unal.edu.co	2022-07-07	15:29:41	Masculino	2	1	A	\N	Sí
\.


--
-- Name: consolidado_chaea_chaea_junior_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY consolidado_chaea_chaea_junior
    ADD CONSTRAINT consolidado_chaea_chaea_junior_pkey PRIMARY KEY (id);


--
-- Name: consolidado_felder_vark_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY consolidado_felder_vark
    ADD CONSTRAINT consolidado_felder_vark_pkey PRIMARY KEY (id);


--
-- Name: cuestionario_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY cuestionario
    ADD CONSTRAINT cuestionario_pkey PRIMARY KEY (id);


--
-- Name: login_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY login
    ADD CONSTRAINT login_pkey PRIMARY KEY (id);


--
-- Name: pregunta_seguridad_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY question_security
    ADD CONSTRAINT pregunta_seguridad_pkey PRIMARY KEY (id);


--
-- Name: programa_curricular_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY programa_curricular
    ADD CONSTRAINT programa_curricular_pkey PRIMARY KEY (codigo_carrera);


--
-- Name: usuario_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY usuario
    ADD CONSTRAINT usuario_pkey PRIMARY KEY (cedula);


--
-- Name: fki_carrera; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX fki_carrera ON usuario USING btree (carrera);


--
-- Name: fki_documento; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX fki_documento ON cuestionario USING btree (documento);


--
-- Name: fki_pregunta; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX fki_pregunta ON usuario USING btree (pregunta);


--
-- Name: carrera; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY usuario
    ADD CONSTRAINT carrera FOREIGN KEY (carrera) REFERENCES programa_curricular(codigo_carrera);


--
-- Name: consolidado_chaea_chaea_junior_documento_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY consolidado_chaea_chaea_junior
    ADD CONSTRAINT consolidado_chaea_chaea_junior_documento_fkey FOREIGN KEY (documento) REFERENCES usuario(cedula);


--
-- Name: consolidado_felder_vark_documento_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY consolidado_felder_vark
    ADD CONSTRAINT consolidado_felder_vark_documento_fkey FOREIGN KEY (documento) REFERENCES usuario(cedula);


--
-- Name: documento; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY cuestionario
    ADD CONSTRAINT documento FOREIGN KEY (documento) REFERENCES usuario(cedula);


--
-- Name: login_cedula_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY login
    ADD CONSTRAINT login_cedula_fkey FOREIGN KEY (cedula) REFERENCES usuario(cedula);


--
-- Name: pregunta; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY usuario
    ADD CONSTRAINT pregunta FOREIGN KEY (pregunta) REFERENCES question_security(id);


--
-- Name: SCHEMA public; Type: ACL; Schema: -; Owner: postgres
--

REVOKE ALL ON SCHEMA public FROM PUBLIC;
REVOKE ALL ON SCHEMA public FROM postgres;
GRANT ALL ON SCHEMA public TO postgres;
GRANT ALL ON SCHEMA public TO PUBLIC;


--
-- PostgreSQL database dump complete
--

