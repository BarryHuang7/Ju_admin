FROM openjdk:8
# 基础镜像使用java

MAINTAINER hj
# 作者

#VOLUME /tmp
# VOLUME 指定临时文件目录为/tmp，在主机/var/lib/docker目录下创建了一个临时文件并链接到容器的/tmp

#ADD Test3-0.0.1-SNAPSHOT.jar test3.jar
# 将jar包添加到容器中并更名为eureka.jar

#RUN bash -c 'touch /test3.jar'
# 运行jar包

# 直接运行这个jar容器
# docker build -t test3:1.0 .
# 第一个挂载-v是实时修改配置文件，第二个挂载是上传图片，第三个挂载是日志文件
# docker run --name test3 -it -d -p 7777:7777 -p 7778:7778 -v /var/lib/docker/volumes/dev-test3/_data:/data -v /home/file:/home/file -v /var/lib/docker/volumes/dev-test3/_data/logs:/data/logs test3:1.0
# 脚本部署：https://www.shuzhiduo.com/A/l1dy73Y6Je/
# 或者自动部署：Jenkins
ENTRYPOINT ["java","-jar","/data/Test3-0.0.1-SNAPSHOT.jar"]

EXPOSE 7777
#暴露7777端口作为微服务