FROM openjdk:8
# 基础镜像使用java

MAINTAINER hj
# 作者

VOLUME /tmp
# VOLUME 指定临时文件目录为/tmp，在主机/var/lib/docker目录下创建了一个临时文件并链接到容器的/tmp

ADD eureka-server-0.0.1-SNAPSHOT.jar eureka-server.jar
# 将jar包添加到容器中并更名为eureka.jar

RUN bash -c 'touch /eureka-server.jar'
# 运行jar包

ENTRYPOINT ["java","-jar","/eureka-server.jar"]

EXPOSE 9999
#暴露9999端口作为微服务